<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReporteInformesPagoController;
use App\Http\Controllers\ReportePrestamosController;
use App\Models\Prestamos;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Smoke test del backend portado (Fase 4): corre contra la BD local `prestamos_gilen`
 * (con datos ya importados), NO contra sqlite. No refresca la BD.
 *
 * Valida que las rutas del panel resuelven, que el middleware `permission:` filtra,
 * y que los controladores responden (Inertia / JSON) sin errores 500.
 */
class PanelSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'prestamos_gilen',
        ]);
        DB::purge('mysql');

        // El front (páginas Vue) es Fase 6; no queremos que Vite/SSR reviente el render.
        $this->withoutVite();

        if (! DB::table('users')->where('email', 'enrony@gmail.com')->exists()) {
            $this->markTestSkipped('BD local sin datos importados (legacy:import-data).');
        }
    }

    private function super(): User
    {
        return User::where('email', 'enrony@gmail.com')->firstOrFail();
    }

    private function cliente(): User
    {
        return User::where('email', 'ruiztovarmariaeugenia@gmail.com')->firstOrFail();
    }

    /**
     * `Controller::isSuperUsuario()` — de la que depende TODO el filtrado por
     * país (`obtenerPaisActivo()`) — se resuelve contra el rol spatie
     * `super-admin`, ya no contra `users_profiles`/`profiles.su` (tablas del
     * RBAC legado eliminadas junto con `modules`/`actions`/etc., PLAN_MIGRACION.md §11).
     */
    public function test_is_super_usuario_via_rol_spatie(): void
    {
        $this->assertTrue(Controller::isSuperUsuario($this->super()->id));
        $this->assertFalse(Controller::isSuperUsuario($this->cliente()->id));
    }

    /** El super-usuario (Gate::before) entra a cualquier índice del panel. */
    public function test_super_accede_a_los_indices_del_panel(): void
    {
        foreach (['/prestamos', '/clientes', '/banks', '/frecuencias', '/payment_report', '/profile', '/profile/usuarios'] as $path) {
            $this->actingAs($this->super())->get($path)->assertSuccessful();
        }
    }

    /** "Cliente Verficado" tiene prestamos.listar pero no banks.listar. */
    public function test_permission_middleware_filtra_por_modulo(): void
    {
        $this->actingAs($this->cliente())->get('/prestamos')->assertSuccessful();
        $this->actingAs($this->cliente())->get('/banks')->assertForbidden();
    }

    /**
     * Los endpoints auxiliares transversales (routes/aux.php) NO se gatean por
     * módulo: "Cliente Verficado" (sin `banks.listar` ni `payment_methods.listar`)
     * puede leerlos porque los consume "Informar un pago".
     */
    public function test_endpoints_auxiliares_no_gateados_por_modulo(): void
    {
        $this->actingAs($this->cliente())->get('/banks')->assertForbidden();

        foreach (['/banks/tables', '/payment_methods/tables', '/franquicias/tables', '/clientes/tables', '/clientes/lista-clientes-json-basic'] as $path) {
            $this->actingAs($this->cliente())->get($path)->assertOk();
        }
    }

    /**
     * Las rutas de escritura exigen el permiso de acción, no solo `.listar`.
     * "Cliente Verficado" tiene `payment_report.listar`+`registrar` y
     * `prestamos.listar`+`registrar`, pero NO `gestionar-informe-de-pago` ni
     * `prestamos.editar`.
     */
    public function test_rutas_de_escritura_exigen_permiso_de_accion(): void
    {
        $cli = $this->cliente();
        $prestamoId = DB::table('prestamos')->orderByDesc('id')->value('id');

        // sin gestionar-informe-de-pago ni editar → 403
        $this->actingAs($cli)
            ->postJson('/payment_report/change_estatus_report', ['data' => json_encode(['id' => 1, 'estatus_actual' => 1, 'estatus_selected' => 4, 'motivo' => 'x', 'support_image' => []])])
            ->assertForbidden();

        // sin prestamos.editar → 403
        $this->actingAs($cli)->putJson("/prestamos/{$prestamoId}/pausar-recargo")->assertForbidden();

        // con prestamos.registrar → pasa el middleware (falla luego por validación, no 403)
        $this->assertNotSame(403, $this->actingAs($cli)->putJson('/prestamos', [])->getStatusCode());
    }

    /** Un endpoint JSON de datos responde sin romper. */
    public function test_endpoint_de_tablas_responde(): void
    {
        $this->actingAs($this->super())
            ->get('/banks/tables')
            ->assertSuccessful();
    }

    /**
     * El país del asistente de préstamos lo determina el grupo de trabajo
     * activo de quien lo usa (Controller::obtenerPaisActivo()), no una lista
     * de países libre — Maria Ruiz (grupo activo -> Colombia) solo ve
     * Colombia; el superusuario, al no tener país fijo, ve los 3 activos.
     */
    public function test_prestamos_tables_pais_por_grupo_activo(): void
    {
        $res = $this->actingAs($this->cliente())->getJson('/prestamos/tables');
        $res->assertOk();
        $this->assertSame(['COL'], collect($res->json('CountryAll'))->pluck('id')->all());

        $resSuper = $this->actingAs($this->super())->getJson('/prestamos/tables');
        $resSuper->assertOk();
        $this->assertEqualsCanonicalizing(
            ['ARG', 'COL', 'VEN'],
            collect($resSuper->json('CountryAll'))->pluck('id')->all(),
        );
    }

    /**
     * Al registrar un préstamo, el país se fuerza al del grupo de trabajo
     * activo de quien lo crea — se ignora cualquier country_id distinto que
     * mande el frontend (mismo trato que grupos_trabajos_user_id).
     */
    public function test_prestamos_store_fuerza_pais_del_grupo_activo(): void
    {
        $cliente = DB::table('clientes')->first();
        $this->assertNotNull($cliente);

        $antes = DB::table('prestamos')->count();

        $this->actingAs($this->cliente())->put('/prestamos', [
            'clienteSelected' => (array) $cliente,
            'country_id' => 'VEN', // intento de mandar un país distinto al activo (COL)
            'tipo_prestamo_id' => 1,
            'monto_prestamo' => 100000,
            'cuota' => 100000,
            'tasa' => 10,
            'total' => 110000,
            'utilidad' => 10000,
            'date_first_pay' => now()->addDays(7)->toDateString(),
            'date_last_pay' => now()->addDays(37)->toDateString(),
            'list_pays' => [
                ['cuota' => 100000, 'date' => now()->addDays(7)->toDateString()],
            ],
        ])->assertRedirect();

        $this->assertSame($antes + 1, DB::table('prestamos')->count());
        $prestamo = DB::table('prestamos')->orderByDesc('id')->first();
        $this->assertSame('COL', $prestamo->country_id);

        // revertir
        DB::table('prestamos_dias')->where('prestamo_id', $prestamo->id)->delete();
        DB::table('prestamos')->where('id', $prestamo->id)->delete();
    }

    /** Todo préstamo nace "Pendiente de aprobación" — no puede recibir un pago informado. */
    public function test_prestamo_nuevo_nace_pendiente_de_aprobacion(): void
    {
        $cliente = DB::table('clientes')->first();
        $this->assertNotNull($cliente);

        $this->actingAs($this->cliente())->put('/prestamos', [
            'clienteSelected' => (array) $cliente,
            'country_id' => 'COL',
            'tipo_prestamo_id' => 1,
            'monto_prestamo' => 100000,
            'cuota' => 100000,
            'tasa' => 10,
            'total' => 110000,
            'utilidad' => 10000,
            'date_first_pay' => now()->addDays(7)->toDateString(),
            'date_last_pay' => now()->addDays(37)->toDateString(),
            'list_pays' => [
                ['cuota' => 100000, 'date' => now()->addDays(7)->toDateString()],
            ],
        ])->assertRedirect();

        $prestamo = DB::table('prestamos')->orderByDesc('id')->first();
        $this->assertSame(Prestamos::APROBACION_PENDIENTE, (int) $prestamo->aprobacion_estatus_id);

        // revertir
        DB::table('prestamos_dias')->where('prestamo_id', $prestamo->id)->delete();
        DB::table('prestamos')->where('id', $prestamo->id)->delete();
    }

    /** Aprobar / rechazar un préstamo: gateado por `prestamos.aprobar` (ningún rol lo tiene por defecto). */
    public function test_prestamos_aprobar_rechazar(): void
    {
        $id = DB::table('prestamos')->orderByDesc('id')->value('id');

        $this->actingAs($this->cliente())->putJson("/prestamos/{$id}/aprobar")->assertForbidden();

        $this->actingAs($this->super())->putJson("/prestamos/{$id}/rechazar")
            ->assertOk()->assertJson(['success' => true, 'aprobacion_estatus_id' => Prestamos::APROBACION_RECHAZADO]);
        $this->assertSame(Prestamos::APROBACION_RECHAZADO, (int) DB::table('prestamos')->where('id', $id)->value('aprobacion_estatus_id'));

        $this->actingAs($this->super())->putJson("/prestamos/{$id}/aprobar")
            ->assertOk()->assertJson(['aprobacion_estatus_id' => Prestamos::APROBACION_APROBADO]);
        $this->assertSame(Prestamos::APROBACION_APROBADO, (int) DB::table('prestamos')->where('id', $id)->value('aprobacion_estatus_id'));
    }

    /**
     * No se puede informar un pago de cuotas contra un préstamo Pendiente de
     * aprobación o Rechazado — ni bypaseando la UI (que ya lo oculta del
     * buscador de "Informar un pago" vía `scopePrestamoActivo`).
     */
    public function test_no_se_puede_informar_pago_de_prestamo_no_aprobado(): void
    {
        $cliente = DB::table('clientes')->first();
        $this->assertNotNull($cliente);

        $cuota = DB::table('prestamos_dias as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.cliente_id', $cliente->id)
            ->where('d.apply', true)->where('d.pagado', false)
            ->orderBy('d.id')->first(['d.id', 'd.cuota', 'p.id as prestamo_id', 'p.aprobacion_estatus_id']);
        $this->assertNotNull($cuota);

        DB::table('prestamos')->where('id', $cuota->prestamo_id)->update(['aprobacion_estatus_id' => Prestamos::APROBACION_PENDIENTE]);

        try {
            $before = DB::table('payment_reports')->count();

            $res = $this->actingAs($this->super())->postJson('/payment_report', [
                'data' => json_encode([
                    'cliente' => (array) $cliente,
                    'tipoPago' => 1,
                    'cuotas' => [['id' => $cuota->id, 'cuota' => (float) $cuota->cuota]],
                    'dataPayments' => [[
                        'payment_method_id' => DB::table('payment_methods')->value('id'),
                        'valor_importe' => (float) $cuota->cuota,
                        'bank_id' => null, 'franquicia_id' => null, 'referencia' => null, 'support_image' => [],
                    ]],
                ]),
            ]);

            $res->assertOk()->assertJson(['success' => false]);
            $this->assertStringContainsString('no está aprobado', $res->json('message'));
            $this->assertSame($before, DB::table('payment_reports')->count());
        } finally {
            DB::table('prestamos')->where('id', $cuota->prestamo_id)->update(['aprobacion_estatus_id' => $cuota->aprobacion_estatus_id]);
        }
    }

    /** `scopePrestamoActivo` (consumido por "Informar un pago") oculta préstamos no aprobados. */
    public function test_informar_pago_no_ofrece_prestamos_no_aprobados(): void
    {
        $prestamo = DB::table('prestamos')
            ->where('pagado', false)->where('anulado', false)->where('perdido', false)->where('estatus', 1)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('prestamos_dias')->whereColumn('prestamos_dias.prestamo_id', 'prestamos.id'))
            ->first();
        $this->assertNotNull($prestamo);

        $antes = $this->actingAs($this->super())->getJson("/prestamos/obtenerPrestamosActivos/{$prestamo->cliente_id}")->json();
        $this->assertContains($prestamo->id, collect($antes)->pluck('id')->all());

        DB::table('prestamos')->where('id', $prestamo->id)->update(['aprobacion_estatus_id' => Prestamos::APROBACION_PENDIENTE]);

        try {
            $despues = $this->actingAs($this->super())->getJson("/prestamos/obtenerPrestamosActivos/{$prestamo->cliente_id}")->json();
            $this->assertNotContains($prestamo->id, collect($despues)->pluck('id')->all());
        } finally {
            DB::table('prestamos')->where('id', $prestamo->id)->update(['aprobacion_estatus_id' => $prestamo->aprobacion_estatus_id]);
        }
    }

    /**
     * `reporte_prestamos.listar` no se le concedió a ningún rol (solo el
     * super-admin entra, vía `Gate::before`) — el admin lo asigna después
     * desde "Roles y permisos" a quien corresponda.
     */
    public function test_reporte_prestamos_permission_gate(): void
    {
        $this->actingAs($this->cliente())->get('/reporte_prestamos')->assertForbidden();
        $this->actingAs($this->super())->get('/reporte_prestamos')->assertSuccessful();
    }

    /**
     * El reporte de préstamos filtra por el país activo del grupo de trabajo,
     * igual que `/prestamos` — y un intento de forzar `?pais=` distinto al
     * propio se ignora para un usuario no-superusuario.
     */
    public function test_reporte_prestamos_filtra_por_pais_activo(): void
    {
        $res = $this->actingAs($this->super())->getJson('/reporte_prestamos/records?pais=VEN');
        $res->assertOk();
        $this->assertNotEmpty($res->json('lista.data'));
        $this->assertContains('Venezuela', collect($res->json('lista.data'))->pluck('pais')->unique()->all());

        // (no hay un usuario "cliente" con permiso propio para este reporte,
        // así que el país activo se prueba a través del super-admin, forzando
        // el filtro explícito arriba, y confirmando abajo que sin filtro trae
        // más de un país.)
        $resTodos = $this->actingAs($this->super())->getJson('/reporte_prestamos/records');
        $resTodos->assertOk();
        $this->assertGreaterThan(
            1,
            collect($resTodos->json('lista.data'))->pluck('pais')->unique()->count(),
        );

        // "Cliente Verficado" no tiene el permiso del reporte (ver gate arriba);
        // se llama al controlador directo para probar igual el filtro de país,
        // ignorando el ?pais= que se le pase.
        $this->actingAs($this->cliente());
        $req = Request::create('/reporte_prestamos/records', 'GET', ['pais' => 'VEN']);
        $lista = (new ReportePrestamosController)->records($req)['lista'];
        $this->assertNotEmpty($lista->items());
        $this->assertSame(['Colombia'], collect($lista->items())->pluck('pais')->unique()->values()->all());
    }

    /** Datos de los selects de filtro (país/ciudad/grupo). */
    public function test_reporte_prestamos_tables(): void
    {
        $res = $this->actingAs($this->super())->getJson('/reportes/filtros/paises');
        $res->assertOk()->assertJsonStructure(['CountryAll']);
        $this->assertEqualsCanonicalizing(
            ['ARG', 'COL', 'VEN'],
            collect($res->json('CountryAll'))->pluck('id')->all(),
        );
    }

    /**
     * Filtros en cascada compartidos por todos los reportes (`routes/shared.php`):
     * ciudades vacío sin país (a propósito), grupos completo sin país y acotado
     * con país, responsables por autocomplete acotado a los grupos indicados.
     */
    public function test_reportes_filtros_en_cascada(): void
    {
        $super = $this->super();

        // Sin país: ninguna ciudad (a propósito, no tiene sentido ofrecerlas todas).
        $this->actingAs($super)->getJson('/reportes/filtros/ciudades')
            ->assertOk()->assertJson(['CitiesAll' => []]);

        // Con país: solo las de ese país.
        $resCiudades = $this->actingAs($super)->getJson('/reportes/filtros/ciudades?pais=COL');
        $resCiudades->assertOk();
        $this->assertNotEmpty($resCiudades->json('CitiesAll'));

        // Grupos: sin país trae todos; con país, menos o igual cantidad.
        $totalGrupos = $this->actingAs($super)->getJson('/reportes/filtros/grupos-trabajo')->json('GruposTrabajoAll');
        $gruposCol = $this->actingAs($super)->getJson('/reportes/filtros/grupos-trabajo?pais=COL')->json('GruposTrabajoAll');
        $this->assertNotEmpty($totalGrupos);
        $this->assertLessThanOrEqual(count($totalGrupos), count($gruposCol));

        // Responsables: sin término, vacío; con término, aparece el superusuario.
        $this->actingAs($super)->getJson('/reportes/filtros/responsables?term=')
            ->assertOk()->assertJson(['ResponsablesAll' => []]);

        $resResp = $this->actingAs($super)->getJson('/reportes/filtros/responsables?term=enrony');
        $resResp->assertOk();
        $this->assertContains($super->email, collect($resResp->json('ResponsablesAll'))->pluck('email')->all());

        // Estados de préstamo (catálogo propio del módulo, no acoplado a /prestamos).
        $this->actingAs($this->cliente())->getJson('/reportes/filtros/estados-prestamo')
            ->assertOk()->assertJsonStructure(['EstadosAll']);
    }

    /** Exportar a Excel/PDF respeta los mismos filtros que el listado en pantalla. */
    public function test_reporte_prestamos_exportar(): void
    {
        $excel = $this->actingAs($this->super())->get('/reporte_prestamos/exportar-excel?pais=VEN');
        $excel->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $excel->headers->get('Content-Type'),
        );

        $pdf = $this->actingAs($this->super())->get('/reporte_prestamos/exportar-pdf?pais=VEN');
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    /**
     * Vista consolidada: los totales y el desglose por estado deben cuadrar
     * con lo que ya se sabe del dataset (mismos filtros que el listado).
     */
    public function test_reporte_prestamos_resumen(): void
    {
        $res = $this->actingAs($this->super())->getJson('/reporte_prestamos/resumen');
        $res->assertOk()->assertJsonStructure([
            'totales' => ['cantidad', 'monto_prestado', 'utilidad', 'monto_perdido'],
            'porEstado' => [['estatus', 'label', 'cantidad', 'tipo']],
        ]);

        $totalPorEstado = collect($res->json('porEstado'))->sum('cantidad');
        $this->assertSame($res->json('totales.cantidad'), $totalPorEstado);
        $this->assertGreaterThan(0, $res->json('totales.monto_prestado'));

        // Filtrando por país, los totales deben achicarse (o quedar iguales), nunca crecer.
        $resFiltrado = $this->actingAs($this->super())->getJson('/reporte_prestamos/resumen?pais=VEN');
        $resFiltrado->assertOk();
        $this->assertLessThanOrEqual($res->json('totales.cantidad'), $resFiltrado->json('totales.cantidad'));
    }

    /** Igual que en /payment_report: nadie tiene el permiso salvo super-admin (Gate::before). */
    public function test_reporte_informes_pago_permission_gate(): void
    {
        $this->actingAs($this->cliente())->get('/reporte_informes_pago')->assertForbidden();
        $this->actingAs($this->super())->get('/reporte_informes_pago')->assertSuccessful();
    }

    /**
     * Mismo criterio de país que `/payment_report/records` (indirecto, vía
     * cuotas -> préstamo), y los filtros nuevos: estado, destino, método de
     * pago y banco.
     */
    public function test_reporte_informes_pago_filtros(): void
    {
        $super = $this->super();

        // País: mismo total que ya prueba test_informes_de_pago_filtra_por_pais_activo.
        $resSuper = $this->actingAs($super)->getJson('/reporte_informes_pago/records');
        $resSuper->assertOk();
        $this->assertSame(16, $resSuper->json('lista.total'));

        $this->actingAs($this->cliente());
        $req = Request::create('/reporte_informes_pago/records', 'GET', ['pais' => 'VEN']);
        $lista = (new ReporteInformesPagoController)->records($req)['lista'];
        $this->assertSame(12, $lista->total());
        $this->assertNotContains('Venezuela', collect($lista->items())->pluck('pais')->unique()->all());

        // Destino: "saldo a favor" (2) es un subconjunto real y distinto de "todos".
        $resDestino = $this->actingAs($super)->getJson('/reporte_informes_pago/records?destinos=2');
        $resDestino->assertOk();
        $this->assertNotEmpty($resDestino->json('lista.data'));
        $this->assertLessThan($resSuper->json('lista.total'), $resDestino->json('lista.total'));
        $this->assertSame([2], collect($resDestino->json('lista.data'))->pluck('destination')->unique()->all());

        // Estado: acota por el último movimiento del informe (mismo criterio que el resto de la app).
        $pendienteId = DB::table('payment_reports_movements_estatus')->where('description', 'Pendiente')->value('id');
        $resEstado = $this->actingAs($super)->getJson("/reporte_informes_pago/records?estados={$pendienteId}");
        $resEstado->assertOk();
        $this->assertNotEmpty($resEstado->json('lista.data'));

        // Método de pago: acota a informes con al menos un método = Efectivo.
        $efectivoId = DB::table('payment_methods')->where('description', 'Efectivo')->value('id');
        $resMetodo = $this->actingAs($super)->getJson("/reporte_informes_pago/records?metodos={$efectivoId}");
        $resMetodo->assertOk();
        $this->assertNotEmpty($resMetodo->json('lista.data'));
        foreach ($resMetodo->json('lista.data') as $fila) {
            $this->assertContains('Efectivo', $fila['metodos']);
        }
    }

    /** Catálogo de estados de informe de pago, servido por el módulo de reportes (no acoplado a payment_report.listar). */
    public function test_reporte_informes_pago_estados_catalogo(): void
    {
        $this->actingAs($this->cliente())->getJson('/reportes/filtros/estados-informe-pago')
            ->assertOk()->assertJsonStructure(['EstadosAll']);
    }

    /** Exportar a Excel/PDF respeta los mismos filtros que el listado en pantalla. */
    public function test_reporte_informes_pago_exportar(): void
    {
        $excel = $this->actingAs($this->super())->get('/reporte_informes_pago/exportar-excel?destinos=2');
        $excel->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $excel->headers->get('Content-Type'),
        );

        $pdf = $this->actingAs($this->super())->get('/reporte_informes_pago/exportar-pdf?destinos=2');
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    /**
     * Vista consolidada: totales + desglose por destino y por estado deben
     * cuadrar entre sí (mismos filtros que el listado).
     */
    public function test_reporte_informes_pago_resumen(): void
    {
        $res = $this->actingAs($this->super())->getJson('/reporte_informes_pago/resumen');
        $res->assertOk()->assertJsonStructure([
            'totales' => ['cantidad', 'monto_total'],
            'porDestino' => [['destination', 'label', 'cantidad', 'monto']],
            'porEstado' => [['estatus', 'label', 'cantidad', 'tipo']],
        ]);

        $this->assertSame(16, $res->json('totales.cantidad'));
        $this->assertSame(16, collect($res->json('porDestino'))->sum('cantidad'));
        $this->assertSame(16, collect($res->json('porEstado'))->sum('cantidad'));
        $this->assertGreaterThan(0, $res->json('totales.monto_total'));

        // Filtrando por destino=2 (saldo a favor), el total de "por destino" coincide con ese subconjunto.
        $resFiltrado = $this->actingAs($this->super())->getJson('/reporte_informes_pago/resumen?destinos=2');
        $resFiltrado->assertOk();
        $this->assertSame([2], collect($resFiltrado->json('porDestino'))->pluck('destination')->all());
        $this->assertLessThan($res->json('totales.cantidad'), $resFiltrado->json('totales.cantidad'));
    }

    /** Pausar / reanudar el recargo por mora de un préstamo. */
    public function test_pausar_recargo_prestamo(): void
    {
        $id = DB::table('prestamos')->orderByDesc('id')->value('id');

        $this->actingAs($this->super())->putJson("/prestamos/{$id}/pausar-recargo")
            ->assertOk()->assertJson(['success' => true, 'pause_surcharge' => true]);
        $this->assertSame(1, (int) DB::table('prestamos')->where('id', $id)->value('pause_surcharge'));

        $this->actingAs($this->super())->putJson("/prestamos/{$id}/pausar-recargo")
            ->assertOk()->assertJson(['pause_surcharge' => false]);
        $this->assertSame(0, (int) DB::table('prestamos')->where('id', $id)->value('pause_surcharge'));
    }

    /** PDF del cartón de pagos: gateado igual que el resto de `/prestamos` (`prestamos.listar`). */
    public function test_prestamos_imprimir_carton(): void
    {
        $id = DB::table('prestamos')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('prestamos_dias')->whereColumn('prestamos_dias.prestamo_id', 'prestamos.id'))
            ->value('id');

        $pdf = $this->actingAs($this->cliente())->get("/prestamos/{$id}/carton");
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $this->actingAs($this->cliente())->get('/prestamos/999999/carton')->assertNotFound();
    }

    /** El comando de recargos corre sin error (bugs `estado`/import del legado). */
    public function test_comando_aplicar_recargos(): void
    {
        $this->artisan('prestamos:aplicar-recargos')->assertOk();
    }

    /** Alta rápida de cliente (usada por el asistente de préstamos). */
    public function test_alta_rapida_cliente(): void
    {
        $antes = DB::table('clientes')->count();

        $res = $this->actingAs($this->super())->putJson('/user/actualizaCliente', [
            'id' => 0,
            'otherForm' => true,
            'paginaActual' => 1,
            'idtipo_documento' => 2,
            'documento' => 'QA-CLI-1',
            'nombre' => 'QA', 'nombre_segundo' => '', 'apellido' => 'Cliente', 'apellido_segundo' => '',
            'telefono' => '3000000000',
            'email' => 'qa.cli@test.com',
            'direccion' => 'Calle QA 1',
            'city_id' => 2261,
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $id = $res->json('id');
        $this->assertSame($antes + 1, DB::table('clientes')->count());
        $this->assertSame(1, (int) DB::table('clientes')->where('id', $id)->value('grupos_trabajos_user_id'));

        DB::table('clientes')->where('id', $id)->delete();
    }

    /** Listado de Informes de pago + su endpoint de datos. */
    public function test_informes_de_pago(): void
    {
        $this->actingAs($this->super())->get('/payment_report')->assertSuccessful();
        $this->actingAs($this->super())
            ->get('/payment_report/records')
            ->assertOk()
            ->assertJsonStructure(['lista' => ['data', 'total', 'current_page']]);
        $this->actingAs($this->super())
            ->get('/PaymentReportsMovementsEstatu/tables')
            ->assertOk()
            ->assertJsonStructure(['PaymentReportsMovementsEstatu']);
    }

    /**
     * El listado de Informes de pago filtra por el país activo del grupo de
     * trabajo — vínculo indirecto (selected_payment_reports -> prestamos_dias
     * -> prestamos.country_id). Un informe sin cuotas seleccionadas ("saldo a
     * favor") no tiene país resoluble y queda visible siempre.
     */
    public function test_informes_de_pago_filtra_por_pais_activo(): void
    {
        $totalSuper = $this->actingAs($this->super())
            ->getJson('/payment_report/records')->json('lista.total');
        $this->assertSame(16, $totalSuper);

        $totalCliente = $this->actingAs($this->cliente())
            ->getJson('/payment_report/records')->json('lista.total');
        $this->assertSame(12, $totalCliente);
    }

    /** Detalle de un informe: préstamos informados + cuotas con `p_seleccionado`. */
    public function test_detalle_informe(): void
    {
        $id = DB::table('selected_payment_reports')
            ->select('payment_report_id')
            ->groupBy('payment_report_id')
            ->orderByRaw('count(*) desc')
            ->value('payment_report_id');
        $this->assertNotNull($id, 'no hay informes con cuotas seleccionadas');

        $res = $this->actingAs($this->super())
            ->get("/payment_report/record/{$id}")
            ->assertOk()
            ->assertJsonStructure(['Prestamos' => [['id', 'monto_prestamo', 'prestamos_dias' => [['id', 'date', 'cuota', 'p_seleccionado']]]]]);

        $seleccionadas = collect($res->json('Prestamos'))
            ->flatMap(fn ($p) => $p['prestamos_dias'])
            ->filter(fn ($d) => $d['p_seleccionado'] === true)
            ->count();
        $this->assertSame(
            DB::table('selected_payment_reports')->where('payment_report_id', $id)->count(),
            $seleccionadas,
        );
    }

    /** Consola de gestión: informes pendientes (estatus 1/4) de un cliente. */
    public function test_gestion_informes_cliente(): void
    {
        $pr = DB::table('payment_reports')
            ->where(fn ($q) => $q->whereNull('payment_reports_movements_estatus_id')->orWhere('payment_reports_movements_estatus_id', 1))
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($pr);

        $res = $this->actingAs($this->super())
            ->getJson("/payment_report/obtenerReportPaymentActivos/{$pr->cliente_id}")
            ->assertOk();

        $filas = $res->json();
        $this->assertNotEmpty($filas);
        $this->assertArrayHasKey('status_description', $filas[0]);
        $this->assertArrayHasKey('value_amount', $filas[0]);
        $this->assertContains($pr->id, array_column($filas, 'id'));
    }

    /** Cambio de estado de un informe de pago (Pendiente -> En revisión). */
    public function test_cambio_de_estado_informe(): void
    {
        $pr = DB::table('payment_reports')
            ->where(fn ($q) => $q->whereNull('payment_reports_movements_estatus_id')->orWhere('payment_reports_movements_estatus_id', 1))
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($pr);

        $movsAntes = DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->count();

        $res = $this->actingAs($this->super())->postJson('/payment_report/change_estatus_report', [
            'data' => json_encode([
                'id' => $pr->id,
                'estatus_actual' => 1,
                'estatus_selected' => 4, // En revisión
                'motivo' => 'QA feature test',
                'support_image' => [],
            ]),
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertSame(4, (int) DB::table('payment_reports')->where('id', $pr->id)->value('payment_reports_movements_estatus_id'));
        $this->assertSame($movsAntes + 1, DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->count());

        // revertir
        DB::table('payment_reports')->where('id', $pr->id)->update(['payment_reports_movements_estatus_id' => null]);
        DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->where('motivo', 'QA feature test')->delete();
    }

    /** Cambio de estado con soporte: guarda el archivo y la fila de soporte. */
    public function test_cambio_de_estado_con_soporte(): void
    {
        Storage::fake('supports_change_estatus_report');

        $pr = DB::table('payment_reports')
            ->where(fn ($q) => $q->whereNull('payment_reports_movements_estatus_id')->orWhere('payment_reports_movements_estatus_id', 1))
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($pr);

        // PNG 1x1 transparente
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $res = $this->actingAs($this->super())->postJson('/payment_report/change_estatus_report', [
            'data' => json_encode([
                'id' => $pr->id,
                'estatus_actual' => 1,
                'estatus_selected' => 4,
                'motivo' => 'QA soporte',
                'support_image' => [['name' => 'sop.png', 'extension' => 'png', 'base64' => $png]],
            ]),
        ]);
        $res->assertOk()->assertJson(['success' => true]);

        $mov = DB::table('payment_reports_movements')
            ->where('payment_report_id', $pr->id)->where('motivo', 'QA soporte')
            ->orderByDesc('id')->first();
        $this->assertNotNull($mov);

        $sop = DB::table('payment_reports_support_movements')
            ->where('payment_reports_movement_id', $mov->id)->first();
        $this->assertNotNull($sop, 'no se registró la fila de soporte');
        Storage::disk('supports_change_estatus_report')->assertExists($sop->soporte);

        // revertir
        DB::table('payment_reports_support_movements')->where('payment_reports_movement_id', $mov->id)->delete();
        DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->where('motivo', 'QA soporte')->delete();
        DB::table('payment_reports')->where('id', $pr->id)->update(['payment_reports_movements_estatus_id' => null]);
    }

    /**
     * Un informe en estado final (finish_estatus=1: Aprobado/Rechazado/Remitido) no
     * admite más cambios, ni siquiera llamando directo al endpoint (bypaseando la UI,
     * que ya deshabilita el modal en ese caso).
     */
    public function test_no_se_puede_cambiar_estado_de_informe_finalizado(): void
    {
        $pr = DB::table('payment_reports')
            ->where(fn ($q) => $q->whereNull('payment_reports_movements_estatus_id')->orWhere('payment_reports_movements_estatus_id', 1))
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($pr);

        $movId = DB::table('payment_reports_movements')->insertGetId([
            'payment_report_id' => $pr->id,
            'estatus' => 3, // Rechazado (finish_estatus = 1)
            'grupos_trabajos_user_id' => null,
            'motivo' => 'QA finalizado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_reports')->where('id', $pr->id)->update(['payment_reports_movements_estatus_id' => 3]);

        $movsAntes = DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->count();

        $res = $this->actingAs($this->super())->postJson('/payment_report/change_estatus_report', [
            'data' => json_encode([
                'id' => $pr->id,
                'estatus_actual' => 3,
                'estatus_selected' => 2,
                'motivo' => 'QA intento forzado',
                'support_image' => [],
            ]),
        ]);

        $res->assertOk()->assertJson([
            'success' => false,
            'message' => 'Este informe está en un estado final y no se puede modificar.',
        ]);
        $this->assertSame(3, (int) DB::table('payment_reports')->where('id', $pr->id)->value('payment_reports_movements_estatus_id'));
        $this->assertSame($movsAntes, DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->count());

        // revertir
        DB::table('payment_reports_movements')->where('id', $movId)->delete();
        DB::table('payment_reports')->where('id', $pr->id)->update(['payment_reports_movements_estatus_id' => null]);
    }

    /** "Informar un pago": crea el informe con cuotas seleccionadas + método + saldo a favor. */
    public function test_informar_un_pago(): void
    {
        $cliente = DB::table('clientes')->first();
        $this->assertNotNull($cliente);

        $cuotas = DB::table('prestamos_dias as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.cliente_id', $cliente->id)
            ->where('d.apply', true)->where('d.pagado', false)
            ->orderBy('d.id')->limit(2)
            ->get(['d.id', 'd.cuota']);
        $this->assertCount(2, $cuotas);

        $sumaCuotas = $cuotas->sum(fn ($c) => (float) $c->cuota);
        $before = DB::table('payment_reports')->count();

        $res = $this->actingAs($this->super())->postJson('/payment_report', [
            'data' => json_encode([
                'cliente' => (array) $cliente,
                'tipoPago' => 1,
                'cuotas' => $cuotas->map(fn ($c) => ['id' => $c->id, 'cuota' => (float) $c->cuota])->all(),
                'dataPayments' => [[
                    'payment_method_id' => DB::table('payment_methods')->value('id'),
                    'valor_importe' => $sumaCuotas + 3000,
                    'bank_id' => null, 'franquicia_id' => null, 'referencia' => null, 'support_image' => [],
                ]],
            ]),
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $pr = DB::table('payment_reports')->orderByDesc('id')->first();
        $this->assertSame($before + 1, DB::table('payment_reports')->count());
        $this->assertSame(3000.0, (float) $pr->importe); // saldo a favor
        $this->assertSame(2, DB::table('selected_payment_reports')->where('payment_report_id', $pr->id)->count());
        $this->assertSame(1, DB::table('payment_reports_methods')->where('payment_report_id', $pr->id)->count());

        // revertir
        DB::table('selected_payment_reports')->where('payment_report_id', $pr->id)->delete();
        DB::table('payment_reports_methods')->where('payment_report_id', $pr->id)->delete();
        DB::table('payment_reports_movements')->where('payment_report_id', $pr->id)->delete();
        DB::table('payment_reports')->where('id', $pr->id)->delete();
    }

    /**
     * Una cuota con un informe de pago en curso (no finalizado) no puede
     * volver a informarse — ni bypaseando la UI llamando directo al endpoint.
     */
    public function test_no_se_puede_informar_pago_de_cuota_ya_reservada(): void
    {
        $cliente = DB::table('clientes')->first();
        $this->assertNotNull($cliente);

        $cuota = DB::table('prestamos_dias as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.cliente_id', $cliente->id)
            ->where('d.apply', true)->where('d.pagado', false)
            ->orderBy('d.id')->first(['d.id', 'd.cuota']);
        $this->assertNotNull($cuota);

        // Reservamos la cuota "a mano": un informe Pendiente con esa cuota seleccionada.
        $prId = DB::table('payment_reports')->insertGetId([
            'cliente_id' => $cliente->id,
            'cliente' => json_encode((array) $cliente),
            'destination' => 1,
            'importe' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('selected_payment_reports')->insert([
            'payment_report_id' => $prId,
            'prestamos_dia_id' => $cuota->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_reports_movements')->insert([
            'payment_report_id' => $prId,
            'estatus' => 1, // Pendiente (finish_estatus = 0)
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->actingAs($this->super())->postJson('/payment_report', [
            'data' => json_encode([
                'cliente' => (array) $cliente,
                'tipoPago' => 1,
                'cuotas' => [['id' => $cuota->id, 'cuota' => (float) $cuota->cuota]],
                'dataPayments' => [[
                    'payment_method_id' => DB::table('payment_methods')->value('id'),
                    'valor_importe' => (float) $cuota->cuota,
                    'bank_id' => null, 'franquicia_id' => null, 'referencia' => null, 'support_image' => [],
                ]],
            ]),
        ]);

        $res->assertOk()->assertJson(['success' => false]);
        $this->assertStringContainsString((string) $cuota->id, (string) $res->json('message'));
        $this->assertSame(1, DB::table('selected_payment_reports')->where('prestamos_dia_id', $cuota->id)->count());

        // revertir
        DB::table('selected_payment_reports')->where('payment_report_id', $prId)->delete();
        DB::table('payment_reports_movements')->where('payment_report_id', $prId)->delete();
        DB::table('payment_reports')->where('id', $prId)->delete();
    }

    /**
     * Si el informe que reservaba la cuota fue rechazado, la cuota vuelve a
     * estar disponible sin ninguna acción manual de "liberar": la reserva
     * siempre se evalúa contra el último movimiento del informe.
     */
    public function test_cuota_se_libera_si_el_informe_que_la_reservaba_fue_rechazado(): void
    {
        $cliente = DB::table('clientes')->first();
        $this->assertNotNull($cliente);

        $cuota = DB::table('prestamos_dias as d')
            ->join('prestamos as p', 'p.id', '=', 'd.prestamo_id')
            ->where('p.cliente_id', $cliente->id)
            ->where('d.apply', true)->where('d.pagado', false)
            ->orderBy('d.id')->first(['d.id', 'd.cuota']);
        $this->assertNotNull($cuota);

        $prId = DB::table('payment_reports')->insertGetId([
            'cliente_id' => $cliente->id,
            'cliente' => json_encode((array) $cliente),
            'destination' => 1,
            'importe' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('selected_payment_reports')->insert([
            'payment_report_id' => $prId,
            'prestamos_dia_id' => $cuota->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_reports_movements')->insert([
            ['payment_report_id' => $prId, 'estatus' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['payment_report_id' => $prId, 'estatus' => 3, 'created_at' => now()->addSecond(), 'updated_at' => now()->addSecond()], // Rechazado, después
        ]);

        $res = $this->actingAs($this->super())->postJson('/payment_report', [
            'data' => json_encode([
                'cliente' => (array) $cliente,
                'tipoPago' => 1,
                'cuotas' => [['id' => $cuota->id, 'cuota' => (float) $cuota->cuota]],
                'dataPayments' => [[
                    'payment_method_id' => DB::table('payment_methods')->value('id'),
                    'valor_importe' => (float) $cuota->cuota,
                    'bank_id' => null, 'franquicia_id' => null, 'referencia' => null, 'support_image' => [],
                ]],
            ]),
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $nuevoPr = DB::table('payment_reports')->orderByDesc('id')->first();

        // revertir (informe viejo rechazado + el nuevo que creó el test)
        DB::table('selected_payment_reports')->whereIn('payment_report_id', [$prId, $nuevoPr->id])->delete();
        DB::table('payment_reports_methods')->where('payment_report_id', $nuevoPr->id)->delete();
        DB::table('payment_reports_movements')->whereIn('payment_report_id', [$prId, $nuevoPr->id])->delete();
        DB::table('payment_reports')->whereIn('id', [$prId, $nuevoPr->id])->delete();
    }

    /** Las pantallas de maestros (CRUD genérico) resuelven. */
    public function test_maestros_resuelven(): void
    {
        foreach ([
            '/payment_forms',
            '/payment_methods',
            '/franquicias',
            '/bank_account_types',
            '/type_payment_record',
            '/banks',
            '/tipo_documentos',
            '/frecuencias',
            '/festivos',
            '/departamentos',
            '/cities',
            '/grupos_trabajo',
            '/tipo_prestamo',
        ] as $path) {
            $this->actingAs($this->super())->get($path)->assertSuccessful();
        }

        // endpoints de datos auxiliares para los selects
        $this->actingAs($this->super())->get('/banks/tables')->assertOk()->assertJsonStructure(['CountryAll']);
        $this->actingAs($this->super())->get('/frecuencias/tables')->assertOk()->assertJsonStructure(['TipoFrecuenciaPrestamoAll']);
        $this->actingAs($this->super())->get('/tipo_prestamo/tables')->assertOk()->assertJsonStructure(['TipoFrecuenciaPrestamoAll']);
        $this->actingAs($this->super())->get('/departamentos/tables')->assertOk()->assertJsonStructure(['CountryAll']);
        $this->actingAs($this->super())->get('/cities/tables')->assertOk()->assertJsonStructure(['CountryAll', 'DepartmentAll']);
        $this->actingAs($this->super())->get('/grupos_trabajo/tables')->assertOk()->assertJsonStructure(['CitiesAll']);
    }

    /** Alta + edición + borrado de un maestro (Franquicia) por el flujo real. */
    public function test_maestro_crud_franquicia(): void
    {
        $super = $this->super();

        $this->actingAs($super)
            ->put('/franquicias', ['code' => 'QA', 'description' => 'QA test', 'paginaActual' => 1])
            ->assertRedirect();

        $fr = DB::table('franquicias')->where('code', 'QA')->first();
        $this->assertNotNull($fr);

        $this->actingAs($super)
            ->put('/franquicias', ['id' => $fr->id, 'code' => 'QA', 'description' => 'QA editado', 'paginaActual' => 1])
            ->assertRedirect();
        $this->assertSame('QA editado', DB::table('franquicias')->where('id', $fr->id)->value('description'));

        $this->actingAs($super)->delete("/franquicias/{$fr->id}/1")->assertRedirect();
        $this->assertNull(DB::table('franquicias')->where('id', $fr->id)->first());
    }

    /**
     * Grupo de trabajo: el código se pide al endpoint dedicado (para
     * mostrarlo en pantalla antes de guardar) y, si el que se manda al crear
     * ya es ese, se respeta tal cual — no se regenera solo. Al editar sin
     * tocar el código tampoco se regenera (el propio registro no cuenta como
     * "ya tomado" contra sí mismo).
     */
    public function test_grupo_trabajo_codigo(): void
    {
        $super = $this->super();
        $city = DB::table('cities')->value('id');

        $codigo = $this->actingAs($super)
            ->getJson('/grupos_trabajo/generateCode')
            ->assertOk()
            ->json('code');
        $this->assertNotEmpty($codigo);

        $this->actingAs($super)->put('/grupos_trabajo', [
            'id' => 0,
            'nombre' => 'QA Grupo '.uniqid(),
            'city_id' => $city,
            'code' => $codigo,
            'paginaActual' => 1,
        ])->assertRedirect();

        $grupo = DB::table('grupos_trabajos')->where('code', $codigo)->first();
        $this->assertNotNull($grupo, 'el grupo se creó con el código mostrado en pantalla');

        $this->actingAs($super)->put('/grupos_trabajo', [
            'id' => $grupo->id,
            'nombre' => 'QA Grupo editado',
            'city_id' => $city,
            'code' => $codigo,
            'paginaActual' => 1,
        ])->assertRedirect();

        $this->assertSame($codigo, DB::table('grupos_trabajos')->where('id', $grupo->id)->value('code'));

        DB::table('grupos_trabajos')->where('id', $grupo->id)->delete();
    }

    /** Alta + edición + borrado de un tipo de préstamo (maestro con select de frecuencia). */
    public function test_maestro_crud_tipo_prestamo(): void
    {
        $super = $this->super();
        $freq = DB::table('tipo_frecuencia_prestamos')->value('id');

        $this->actingAs($super)->put('/tipo_prestamo', [
            'id' => 0,
            'descripcion' => 'QA Tipo',
            'cantidad' => 10,
            'tipo_frecuencia_prestamo_id' => $freq,
            'paginaActual' => 1,
        ])->assertRedirect();

        $tp = DB::table('tipo_prestamos')->where('descripcion', 'QA Tipo')->first();
        $this->assertNotNull($tp);
        $this->assertSame(10, (int) $tp->cantidad);

        $this->actingAs($super)->put('/tipo_prestamo', [
            'id' => $tp->id,
            'descripcion' => 'QA Tipo',
            'cantidad' => 15,
            'tipo_frecuencia_prestamo_id' => $freq,
            'paginaActual' => 1,
        ])->assertRedirect();
        $this->assertSame(15, (int) DB::table('tipo_prestamos')->where('id', $tp->id)->value('cantidad'));

        $this->actingAs($super)->delete("/tipo_prestamo/{$tp->id}/1")->assertRedirect();
        $this->assertNull(DB::table('tipo_prestamos')->where('id', $tp->id)->first());
    }

    /** Pantalla de Clientes: índice + alta / edición / baja lógica (PUT|DELETE /clientes). */
    public function test_clientes_crud(): void
    {
        $super = $this->super();

        $this->actingAs($super)->get('/clientes')->assertSuccessful();

        $td = DB::table('tipos_documentos')->value('id');
        $city = DB::table('cities')->value('id');
        $antes = DB::table('clientes')->where('estatus', 1)->count();

        $this->actingAs($super)->put('/clientes', [
            'id' => 0,
            'idtipo_documento' => $td,
            'documento' => 'QA-CLI-SCREEN',
            'nombre' => 'QA', 'nombre_segundo' => '', 'apellido' => 'Pantalla', 'apellido_segundo' => '',
            'telefono' => '3000000001',
            'email' => 'qa.pantalla@test.com',
            'direccion' => 'Calle QA 2',
            'city_id' => $city,
            'paginaActual' => 1,
        ])->assertRedirect();

        $cli = DB::table('clientes')->where('documento', 'QA-CLI-SCREEN')->first();
        $this->assertNotNull($cli);
        $this->assertSame($antes + 1, DB::table('clientes')->where('estatus', 1)->count());

        $this->actingAs($super)->put('/clientes', [
            'id' => $cli->id,
            'idtipo_documento' => $cli->idtipo_documento,
            'documento' => $cli->documento,
            'nombre' => 'QA editado', 'nombre_segundo' => '', 'apellido' => 'Pantalla', 'apellido_segundo' => '',
            'telefono' => '3000000001',
            'email' => 'qa.pantalla@test.com',
            'direccion' => 'Calle QA 2',
            'city_id' => $cli->city_id,
            'paginaActual' => 1,
        ])->assertRedirect();
        $this->assertSame('QA editado', DB::table('clientes')->where('id', $cli->id)->value('nombre'));

        $this->actingAs($super)->delete("/clientes/{$cli->id}/1")->assertRedirect();
        $this->assertSame(0, (int) DB::table('clientes')->where('id', $cli->id)->value('estatus'));

        DB::table('clientes')->where('id', $cli->id)->delete();
    }

    /** Admin/RBAC: alta de rol con permisos + edición + borrado; rol protegido intacto. */
    public function test_admin_roles(): void
    {
        $super = $this->super();
        $this->actingAs($super)->get('/profile')->assertSuccessful();

        $perms = DB::table('permissions')->whereIn('name', ['clientes.listar', 'clientes.registrar'])->pluck('name')->all();

        $this->actingAs($super)->put('/profile', [
            'id' => 0,
            'name' => 'QA Rol',
            'permissions' => $perms,
        ])->assertRedirect();

        $rol = DB::table('roles')->where('name', 'QA Rol')->first();
        $this->assertNotNull($rol);
        $this->assertSame(2, DB::table('role_has_permissions')->where('role_id', $rol->id)->count());

        // edición: quita un permiso
        $this->actingAs($super)->put('/profile', [
            'id' => $rol->id,
            'name' => 'QA Rol',
            'permissions' => ['clientes.listar'],
        ])->assertRedirect();
        $this->assertSame(1, DB::table('role_has_permissions')->where('role_id', $rol->id)->count());

        // el rol protegido no se puede borrar
        $sa = DB::table('roles')->where('name', 'super-admin')->first();
        $this->actingAs($super)->delete("/profile/roles/{$sa->id}")->assertSessionHasErrors('role');
        $this->assertNotNull(DB::table('roles')->where('id', $sa->id)->first());

        $this->actingAs($super)->delete("/profile/roles/{$rol->id}")->assertRedirect();
        $this->assertNull(DB::table('roles')->where('id', $rol->id)->first());
    }

    /** Admin/RBAC: sincroniza los roles de un usuario. */
    public function test_admin_usuarios(): void
    {
        $super = $this->super();
        $this->actingAs($super)->get('/profile/usuarios')->assertSuccessful();

        $u = $this->cliente();
        $antes = $u->getRoleNames()->all();

        $this->actingAs($super)->put("/profile/usuarios/{$u->id}", [
            'roles' => ['Cliente Verficado', 'Inversionista V'],
        ])->assertRedirect();

        $u->unsetRelation('roles');
        $this->assertEqualsCanonicalizing(['Cliente Verficado', 'Inversionista V'], $u->getRoleNames()->all());

        // revertir
        $this->actingAs($super)->put("/profile/usuarios/{$u->id}", ['roles' => $antes])->assertRedirect();
    }

    /**
     * Admin/RBAC: sincroniza en qué países puede un usuario crear un grupo de
     * trabajo (`user_countries`, redefinido — no es "en qué país opera").
     */
    public function test_admin_usuarios_paises(): void
    {
        $super = $this->super();
        $u = $this->cliente();
        $antes = $u->ownedUserCountry()->pluck('country_id')->all();

        $this->actingAs($super)->put("/profile/usuarios/{$u->id}", [
            'paises' => ['ARG', 'VEN'],
        ])->assertRedirect();

        $u->unsetRelation('ownedUserCountry');
        $this->assertEqualsCanonicalizing(['ARG', 'VEN'], $u->ownedUserCountry->pluck('country_id')->all());

        // un payload que solo manda roles no debe tocar los países
        $this->actingAs($super)->put("/profile/usuarios/{$u->id}", [
            'roles' => $u->getRoleNames()->all(),
        ])->assertRedirect();

        $u->unsetRelation('ownedUserCountry');
        $this->assertEqualsCanonicalizing(['ARG', 'VEN'], $u->ownedUserCountry->pluck('country_id')->all());

        // revertir
        $this->actingAs($super)->put("/profile/usuarios/{$u->id}", ['paises' => $antes])->assertRedirect();
    }

    /** Ajustes: perfil (Fortify) resuelve y el nombre se actualiza. */
    public function test_settings_perfil(): void
    {
        $super = $this->super();

        $this->actingAs($super)->get('/settings/profile')->assertSuccessful();
        $this->actingAs($super)->get('/settings/appearance')->assertSuccessful();

        $orig = $super->name;
        $this->actingAs($super)->patch('/settings/profile', [
            'name' => 'QA Nombre',
            'email' => $super->email,
        ])->assertRedirect();
        $this->assertSame('QA Nombre', DB::table('users')->where('id', $super->id)->value('name'));

        DB::table('users')->where('id', $super->id)->update(['name' => $orig]);
    }
}
