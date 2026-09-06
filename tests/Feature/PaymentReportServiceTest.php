<?php

namespace Tests\Feature;

use App\Models\CustomerMovementHistory;
use App\Models\PaymentReport;
use App\Models\User;
use App\Services\PaymentReportService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de lógica de `PaymentReportService` (Fase 7). Corre contra la BD local
 * `prestamos_gilen` con datos importados (igual que PanelSmokeTest); no la refresca.
 * Cada test restaura lo que toca.
 */
class PaymentReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'prestamos_gilen',
        ]);
        DB::purge('mysql');

        if (! DB::table('users')->where('email', 'enrony@gmail.com')->exists()) {
            $this->markTestSkipped('BD local sin datos importados.');
        }

        $this->actingAs($this->super());
    }

    private function super(): User
    {
        return User::where('email', 'enrony@gmail.com')->firstOrFail();
    }

    private function svc(): PaymentReportService
    {
        $s = new PaymentReportService;
        $s->grupos_trabajos_user_id = 1;

        return $s;
    }

    // ------------------------------------------------------------------
    //  registerPositiveBalance: cálculo del saldo a favor
    // ------------------------------------------------------------------

    /** tipoPago 1 (cuotas): importe = Σ pagos − Σ cuotas, cuando es positivo. */
    public function test_saldo_a_favor_cuotas_positivo(): void
    {
        $id = DB::table('payment_reports')->orderByDesc('id')->value('id');
        $orig = DB::table('payment_reports')->where('id', $id)->value('importe');

        $s = $this->svc();
        $s->PaymentReport = PaymentReport::findOrFail($id);
        $s->data = [
            'tipoPago' => 1,
            'dataPayments' => [['valor_importe' => 50000], ['valor_importe' => 5000]],
            'cuotas' => [['cuota' => 30000], ['cuota' => 12000]],
        ];
        $s->registerPositiveBalance();

        $this->assertSame(13000.0, (float) DB::table('payment_reports')->where('id', $id)->value('importe'));

        DB::table('payment_reports')->where('id', $id)->update(['importe' => $orig]);
    }

    /** tipoPago 1: si Σ pagos ≤ Σ cuotas el importe NO se toca. */
    public function test_saldo_a_favor_cuotas_no_positivo_no_cambia(): void
    {
        $id = DB::table('payment_reports')->orderByDesc('id')->value('id');
        DB::table('payment_reports')->where('id', $id)->update(['importe' => 777]);

        $s = $this->svc();
        $s->PaymentReport = PaymentReport::findOrFail($id);
        $s->data = [
            'tipoPago' => 1,
            'dataPayments' => [['valor_importe' => 10000]],
            'cuotas' => [['cuota' => 30000]],
        ];
        $s->registerPositiveBalance();

        $this->assertSame(777.0, (float) DB::table('payment_reports')->where('id', $id)->value('importe'));

        DB::table('payment_reports')->where('id', $id)->update(['importe' => 0]);
    }

    /** tipoPago 2 (saldo a favor): importe = Σ pagos, siempre. */
    public function test_saldo_a_favor_destino_saldo(): void
    {
        $id = DB::table('payment_reports')->orderByDesc('id')->value('id');
        $orig = DB::table('payment_reports')->where('id', $id)->value('importe');

        $s = $this->svc();
        $s->PaymentReport = PaymentReport::findOrFail($id);
        $s->data = [
            'tipoPago' => 2,
            'dataPayments' => [['valor_importe' => 8000], ['valor_importe' => 1500]],
        ];
        $s->registerPositiveBalance();

        $this->assertSame(9500.0, (float) DB::table('payment_reports')->where('id', $id)->value('importe'));

        DB::table('payment_reports')->where('id', $id)->update(['importe' => $orig]);
    }

    // ------------------------------------------------------------------
    //  processPaymentCouotas
    // ------------------------------------------------------------------

    /** Marca `pagado` solo las cuotas que estaban pendientes; las ya pagadas van a prestamosIdNotProccess. */
    public function test_procesar_cuotas_marca_pendientes_y_reporta_ya_pagadas(): void
    {
        $pendientes = DB::table('prestamos_dias')->where('pagado', false)->where('apply', true)
            ->orderBy('id')->limit(2)->pluck('id')->all();
        $yaPagada = DB::table('prestamos_dias')->where('pagado', true)->orderBy('id')->value('id');
        $this->assertCount(2, $pendientes);
        $this->assertNotNull($yaPagada);

        $s = $this->svc();
        $s->processPaymentCouotas([...$pendientes, $yaPagada]);

        foreach ($pendientes as $pid) {
            $this->assertSame(1, (int) DB::table('prestamos_dias')->where('id', $pid)->value('pagado'));
        }
        $this->assertSame([$yaPagada], $s->prestamosIdNotProccess);

        DB::table('prestamos_dias')->whereIn('id', $pendientes)->update(['pagado' => false]);
    }

    // ------------------------------------------------------------------
    //  processSummaryCustomer
    // ------------------------------------------------------------------

    /** Operación 'S' (Abono/Devolución): suma a balance y credit. */
    public function test_resumen_cliente_operacion_s_suma_credito(): void
    {
        $cli = DB::table('clientes')
            ->leftJoin('summary_customer_movements as s', 's.cliente_id', '=', 'clientes.id')
            ->whereNull('s.id')->value('clientes.id');
        $this->assertNotNull($cli);

        $cmh = new CustomerMovementHistory;
        $cmh->cliente_id = $cli;
        $cmh->type_movement_id = 4; // Abono → operation 'S'
        $cmh->amount = 5000;

        $this->svc()->processSummaryCustomer($cmh);

        $row = DB::table('summary_customer_movements')->where('cliente_id', $cli)->first();
        $this->assertNotNull($row);
        $this->assertSame(5000.0, (float) $row->balance);
        $this->assertSame(5000.0, (float) $row->credit);

        DB::table('summary_customer_movements')->where('cliente_id', $cli)->delete();
    }

    /** Operación 'R' (Pago/Retiro): resta de balance y suma a debit. */
    public function test_resumen_cliente_operacion_r_resta_balance(): void
    {
        $cli = DB::table('clientes')
            ->leftJoin('summary_customer_movements as s', 's.cliente_id', '=', 'clientes.id')
            ->whereNull('s.id')->value('clientes.id');
        $this->assertNotNull($cli);

        $cmh = new CustomerMovementHistory;
        $cmh->cliente_id = $cli;
        $cmh->type_movement_id = 1; // Pago → operation 'R'
        $cmh->amount = 3000;

        $this->svc()->processSummaryCustomer($cmh);

        $row = DB::table('summary_customer_movements')->where('cliente_id', $cli)->first();
        $this->assertNotNull($row);
        $this->assertSame(-3000.0, (float) $row->balance);
        $this->assertSame(3000.0, (float) $row->debit);

        DB::table('summary_customer_movements')->where('cliente_id', $cli)->delete();
    }

    // ------------------------------------------------------------------
    //  verifiedCurrentStatus
    // ------------------------------------------------------------------

    /** Lanza si el estado seleccionado ya es el estado actual del informe. */
    public function test_verificar_estado_actual_lanza_si_ya_procesado(): void
    {
        $mov = DB::table('payment_reports_movements')->orderByDesc('id')->first();
        $this->assertNotNull($mov);

        $s = $this->svc();
        $s->data = ['id' => $mov->payment_report_id, 'estatus_selected' => $mov->estatus];

        $this->expectException(\Exception::class);
        $s->verifiedCurrentStatus();
    }

    /** No lanza si el estado seleccionado es distinto al actual. */
    public function test_verificar_estado_actual_ok_si_distinto(): void
    {
        $mov = DB::table('payment_reports_movements')->orderByDesc('id')->first();

        $s = $this->svc();
        $s->data = ['id' => $mov->payment_report_id, 'estatus_selected' => $mov->estatus === 2 ? 1 : 2];
        $s->verifiedCurrentStatus();

        $this->assertNotNull($s->PaymentReport);
    }
}
