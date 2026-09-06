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
        ] as $path) {
            $this->actingAs($this->super())->get($path)->assertSuccessful();
        }
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
}
