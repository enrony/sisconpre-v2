<?php

namespace App\Services;

use App\Models\PrestamoEstatusHistorial;
use App\Models\Prestamos;
use App\Models\PrestamosDias;
use DomainException;

/**
 * Ciclo de vida del préstamo (`prestamos.estatus`): Pendiente (en curso de
 * cobro) → Pagado (automático al saldar la última cuota) / Anulado / Perdido
 * (manuales, con motivo). Perdido se puede reactivar; Pagado y Anulado son
 * finales. Mantiene en sync los flags legados `pagado`/`anulado`/`perdido`,
 * que todavía filtra `Prestamos::scopePrestamoActivo()`.
 *
 * Las reglas de negocio violadas se informan con `DomainException`.
 */
class PrestamoEstadoService
{
    public function cerrarSiSaldado(int $prestamoId): void
    {
        $prestamo = Prestamos::find($prestamoId);

        if (! $prestamo || (int) $prestamo->estatus !== Prestamos::ESTATUS_PENDIENTE) {
            return;
        }

        $quedanCuotas = $prestamo->prestamos_dias()->where('apply', true)->where('pagado', false)->exists();

        if (! $quedanCuotas) {
            $this->cambiar($prestamo, Prestamos::ESTATUS_PAGADO, 'Todas las cuotas pagadas');
        }
    }

    /** Anular = se registró por error: sin cuotas pagadas ni pagos informados en curso. */
    public function anular(Prestamos $prestamo, string $motivo): void
    {
        $this->exigirPendiente($prestamo, 'anular');

        if ($prestamo->prestamos_dias()->where('pagado', true)->exists()) {
            throw new DomainException('No se puede anular: el préstamo ya tiene cuotas pagadas.');
        }

        $this->exigirSinInformesEnCurso($prestamo);
        $this->cambiar($prestamo, Prestamos::ESTATUS_ANULADO, $motivo);
    }

    /** Perdido = incobrable. Solo un préstamo aprobado (dinero entregado) puede darse por perdido. */
    public function marcarPerdido(Prestamos $prestamo, string $motivo): void
    {
        $this->exigirPendiente($prestamo, 'marcar como perdido');

        if ((int) $prestamo->aprobacion_estatus_id !== Prestamos::APROBACION_APROBADO) {
            throw new DomainException('Solo un préstamo aprobado puede darse por perdido.');
        }

        $this->exigirSinInformesEnCurso($prestamo);
        $this->cambiar($prestamo, Prestamos::ESTATUS_PERDIDO, $motivo);
    }

    /** El cliente apareció: el préstamo perdido vuelve a estar en curso de cobro. */
    public function reactivar(Prestamos $prestamo, ?string $motivo): void
    {
        if ((int) $prestamo->estatus !== Prestamos::ESTATUS_PERDIDO) {
            throw new DomainException('Solo se puede reactivar un préstamo marcado como perdido.');
        }

        $this->cambiar($prestamo, Prestamos::ESTATUS_PENDIENTE, $motivo);
    }

    private function exigirPendiente(Prestamos $prestamo, string $accion): void
    {
        if ((int) $prestamo->estatus !== Prestamos::ESTATUS_PENDIENTE) {
            throw new DomainException("No se puede {$accion}: el préstamo ya no está en curso.");
        }
    }

    private function exigirSinInformesEnCurso(Prestamos $prestamo): void
    {
        $enCurso = $prestamo->prestamos_dias()
            ->with('pendientesPago.paymentReport.payment_reports_movement_first.estatus_description')
            ->get()
            ->first(function (PrestamosDias $cuota): bool {
                $estado = $cuota->pendientesPago?->paymentReport?->payment_reports_movement_first?->estatus_description;

                return $estado !== null && ! $estado->finish_estatus;
            });

        if ($enCurso) {
            throw new DomainException("El préstamo tiene un pago informado en curso (informe #{$enCurso->pendientesPago?->payment_report_id}). Resuélvalo primero.");
        }
    }

    /** @param Prestamos::ESTATUS_* $nuevo */
    private function cambiar(Prestamos $prestamo, int $nuevo, ?string $motivo): void
    {
        $anterior = (int) $prestamo->estatus;

        $prestamo->estatus = $nuevo;
        $prestamo->pagado = $nuevo === Prestamos::ESTATUS_PAGADO;
        $prestamo->anulado = $nuevo === Prestamos::ESTATUS_ANULADO;
        $prestamo->perdido = $nuevo === Prestamos::ESTATUS_PERDIDO;
        $prestamo->save();

        PrestamoEstatusHistorial::create([
            'prestamo_id' => $prestamo->id,
            'estatus_anterior' => $anterior,
            'estatus_nuevo' => $nuevo,
            'motivo' => $motivo,
            'user_id' => auth()->id(),
        ]);
    }
}
