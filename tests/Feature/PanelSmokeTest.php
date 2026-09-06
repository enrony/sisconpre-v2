<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
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
            $this->markTestSkipped('BD local sin datos importados (legacy:import-data + rbac:sync-from-legacy).');
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

    /** El super-usuario (Gate::before) entra a cualquier índice del panel. */
    public function test_super_accede_a_los_indices_del_panel(): void
    {
        foreach (['/prestamos', '/clientes', '/banks', '/frecuencias', '/payment_report', '/modules'] as $path) {
            $this->actingAs($this->super())->get($path)->assertSuccessful();
        }
    }

    /** "Cliente Verficado" tiene prestamos.listar pero no banks.listar. */
    public function test_permission_middleware_filtra_por_modulo(): void
    {
        $this->actingAs($this->cliente())->get('/prestamos')->assertSuccessful();
        $this->actingAs($this->cliente())->get('/banks')->assertForbidden();
    }

    /** Un endpoint JSON de datos responde sin romper. */
    public function test_endpoint_de_tablas_responde(): void
    {
        $this->actingAs($this->super())
            ->get('/banks/tables')
            ->assertSuccessful();
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

        $res = $this->actingAs($this->super())->postJson('/payment_report/', [
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
        ] as $path) {
            $this->actingAs($this->super())->get($path)->assertSuccessful();
        }

        // endpoints de datos auxiliares para los selects
        $this->actingAs($this->super())->get('/banks/tables')->assertOk()->assertJsonStructure(['CountryAll']);
        $this->actingAs($this->super())->get('/frecuencias/tables')->assertOk()->assertJsonStructure(['TipoFrecuenciaPrestamoAll']);
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
}
