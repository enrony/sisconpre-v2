<?php

namespace App\Http\Controllers;

use App\Models\PaymentReport;
use App\Models\Prestamos;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Métricas y series para el panel principal.
 *
 * Nota de alcance: igual que el resto de los listados (`Prestamos::lista()`,
 * `PaymentReport::lista()`), estas consultas NO filtran por
 * `grupos_trabajos_user_id` — la multi-tenencia por grupo de trabajo está en
 * las tablas pero todavía no se aplica en ninguna lectura de la app (hueco
 * documentado en PLAN_MIGRACION.md §9). Cuando se cierre ese hueco a nivel
 * general, este controlador debe sumar el mismo filtro.
 */
class DashboardController extends Controller
{
    /** Préstamos "Pendiente": en curso, con saldo por cobrar. */
    private const ESTATUS_PENDIENTE = 1;

    /** `payment_reports_movements_estatus_id` que todavía no se resolvieron. */
    private const ESTADOS_INFORME_SIN_RESOLVER = [1, 4];

    /** Meses de historial para la serie de desembolsos/cobros. */
    private const MESES_SERIE = 12;

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'kpis' => $this->kpis(),
            'carteraPorEstado' => $this->carteraPorEstado(),
            'serieMensual' => $this->serieMensual(),
        ]);
    }

    /**
     * @return array{
     *     cartera_activa: array{cantidad: int, monto: float},
     *     cuotas_vencidas: array{cantidad: int, monto: float},
     *     informes_por_revisar: int,
     * }
     */
    private function kpis(): array
    {
        $cartera = Prestamos::query()
            ->where('estatus', self::ESTATUS_PENDIENTE)
            ->selectRaw('count(*) as cantidad, coalesce(sum(total), 0) as monto')
            ->first();

        $vencidas = DB::table('prestamos_dias')
            ->join('prestamos', 'prestamos.id', '=', 'prestamos_dias.prestamo_id')
            ->where('prestamos.estatus', self::ESTATUS_PENDIENTE)
            ->where('prestamos_dias.apply', true)
            ->where('prestamos_dias.pagado', false)
            ->where('prestamos_dias.date', '<', now()->toDateString())
            ->selectRaw('count(*) as cantidad, coalesce(sum(prestamos_dias.cuota), 0) as monto')
            ->first();

        $informesPorRevisar = PaymentReport::query()
            ->where('estatus', 1)
            ->whereIn('payment_reports_movements_estatus_id', self::ESTADOS_INFORME_SIN_RESOLVER)
            ->count();

        return [
            'cartera_activa' => [
                'cantidad' => (int) ($cartera->cantidad ?? 0),
                'monto' => (float) ($cartera->monto ?? 0),
            ],
            'cuotas_vencidas' => [
                'cantidad' => (int) ($vencidas->cantidad ?? 0),
                'monto' => (float) ($vencidas->monto ?? 0),
            ],
            'informes_por_revisar' => $informesPorRevisar,
        ];
    }

    /**
     * Distribución de préstamos por estado (Pendiente/Pagado/Anulado/Perdido).
     *
     * @return list<array{estatus: int, label: string, cantidad: int}>
     */
    private function carteraPorEstado(): array
    {
        return array_values(
            DB::table('prestamos')
                ->join('prestamos_estatus', 'prestamos_estatus.id', '=', 'prestamos.estatus')
                ->selectRaw('prestamos_estatus.id as estatus, prestamos_estatus.description as label, count(*) as cantidad')
                ->groupBy('prestamos_estatus.id', 'prestamos_estatus.description')
                ->orderBy('prestamos_estatus.id')
                ->get()
                ->map(fn (object $row): array => [
                    'estatus' => (int) $row->estatus,
                    'label' => (string) $row->label,
                    'cantidad' => (int) $row->cantidad,
                ])
                ->all(),
        );
    }

    /**
     * Serie mensual de desembolsos (préstamos originados) y cobros.
     *
     * "Cobrado" agrupa por la fecha de vencimiento de la cuota (`date`), no
     * por cuándo se marcó pagada: los datos importados del legado no traen
     * poblada una fecha de pago real (`prestamos_dias.fecha_pago`/
     * `fecha_pagado` siempre vienen vacías), así que esta es la mejor
     * aproximación disponible con los datos que hay.
     *
     * @return list<array{mes: string, desembolsado: float, cobrado: float}>
     */
    private function serieMensual(): array
    {
        // Immutable a propósito: se reutiliza como ancla en el map() de abajo
        // sin ir arrastrando meses de una iteración a la siguiente.
        $desde = CarbonImmutable::now()->subMonths(self::MESES_SERIE - 1)->startOfMonth();

        $desembolsos = Prestamos::query()
            ->where('created_at', '>=', $desde)
            ->selectRaw("date_format(created_at, '%Y-%m') as mes, coalesce(sum(monto_prestamo), 0) as monto")
            ->groupBy('mes')
            ->pluck('monto', 'mes');

        $cobros = DB::table('prestamos_dias')
            ->where('apply', true)
            ->where('pagado', true)
            ->where('date', '>=', $desde->toDateString())
            ->selectRaw("date_format(date, '%Y-%m') as mes, coalesce(sum(cuota), 0) as monto")
            ->groupBy('mes')
            ->pluck('monto', 'mes');

        return array_values(
            collect(range(0, self::MESES_SERIE - 1))
                ->map(function (int $i) use ($desde, $desembolsos, $cobros): array {
                    $mes = $desde->addMonths($i)->format('Y-m');

                    return [
                        'mes' => $mes,
                        'desembolsado' => (float) ($desembolsos[$mes] ?? 0),
                        'cobrado' => (float) ($cobros[$mes] ?? 0),
                    ];
                })
                ->all(),
        );
    }
}
