<?php

namespace App\Services;

use App\Models\PaymentReport;
use App\Models\SummaryCustomerMovement;

/**
 * Saldo a favor del cliente (`summary_customer_movements.balance`). Mientras
 * un informe de pago que aplica saldo está en curso (no finalizado), ese
 * monto queda reservado: el saldo recién se descuenta al aprobarse el
 * informe, y si se rechaza simplemente deja de estar reservado.
 */
class SaldoFavorService
{
    /**
     * @return array{saldo: float, reservado: float, disponible: float}
     */
    public function resumen(int $clienteId): array
    {
        $saldo = (float) SummaryCustomerMovement::where('cliente_id', $clienteId)->value('balance');

        $reservado = (float) PaymentReport::query()
            ->where('cliente_id', $clienteId)
            ->where('saldo_favor_aplicado', '>', 0)
            ->with('payment_reports_movement_first.estatus_description')
            ->get()
            ->filter(fn (PaymentReport $r): bool => ! $r->payment_reports_movement_first?->estatus_description->finish_estatus)
            ->sum('saldo_favor_aplicado');

        return [
            'saldo' => round($saldo, 3),
            'reservado' => round($reservado, 3),
            'disponible' => round(max($saldo - $reservado, 0), 3),
        ];
    }
}
