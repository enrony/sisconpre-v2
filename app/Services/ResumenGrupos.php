<?php

namespace App\Services;

use App\Models\Prestamos;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tarjetas "Mis grupos" del Dashboard: un resumen por cada grupo del usuario
 * (no solo el activo), para ver de un vistazo cómo está cada uno y saltar
 * al que necesite atención. Se calcula en el momento, siempre al día.
 *
 * - Personal: cartera activa, a cobrar hoy, vencido, pagos por revisar y
 *   préstamos por aprobar del grupo.
 * - Cliente: sus préstamos activos en el grupo, saldo pendiente, próxima
 *   cuota, cuotas vencidas y pagos en revisión.
 *
 * Mismo criterio que el resto de la app: lo registrado por los miembros del
 * grupo, en el país del grupo.
 */
class ResumenGrupos
{
    /** `payment_reports_movements_estatus`: Pendiente y En revisión. */
    private const INFORME_EN_CURSO = [1, 4];

    public function __construct(private AlcanceCartera $alcance) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function para(User $user): array
    {
        $clienteId = $this->alcance->esCliente() ? $this->alcance->clienteId() : null;
        $hoy = CarbonImmutable::today()->toDateString();

        $tarjetas = [];
        foreach (GrupoActivo::opciones($user) as $grupo) {
            $gtus = array_values(DB::table('grupos_trabajos_users')->where('idgrupo_trabajo', $grupo['grupo_id'])->pluck('id')->all());
            $pais = DB::table('grupos_trabajos as g')->join('cities as c', 'c.id', '=', 'g.city_id')
                ->where('g.id', $grupo['grupo_id'])->value('c.country_id');

            $tarjetas[] = $grupo + [
                'tipo' => $clienteId ? 'cliente' : 'personal',
                'datos' => $clienteId
                    ? $this->delCliente($clienteId, $gtus, $pais, $hoy)
                    : $this->delPersonal($gtus, $pais, $hoy),
            ];
        }

        return $tarjetas;
    }

    /**
     * @param  list<mixed>  $gtus
     * @return array<string, mixed>
     */
    private function delPersonal(array $gtus, mixed $pais, string $hoy): array
    {
        $cartera = $this->prestamos($gtus, $pais)
            ->where('prestamos.estatus', Prestamos::ESTATUS_PENDIENTE)
            ->selectRaw('count(*) as cantidad, coalesce(sum(prestamos.total), 0) as monto')
            ->first();

        $cuotas = $this->cuotasPendientes($gtus, $pais)
            ->selectRaw('coalesce(sum(case when d.date = ? then d.cuota else 0 end), 0) as hoy_monto,
                coalesce(sum(case when d.date = ? then 1 else 0 end), 0) as hoy_cantidad,
                coalesce(sum(case when d.date < ? then d.cuota else 0 end), 0) as vencido_monto,
                coalesce(sum(case when d.date < ? then 1 else 0 end), 0) as vencido_cantidad', [$hoy, $hoy, $hoy, $hoy])
            ->first();

        return [
            'cartera_activa' => ['cantidad' => (int) ($cartera->cantidad ?? 0), 'monto' => (float) ($cartera->monto ?? 0)],
            'cobrar_hoy' => ['cantidad' => (int) ($cuotas->hoy_cantidad ?? 0), 'monto' => (float) ($cuotas->hoy_monto ?? 0)],
            'vencido' => ['cantidad' => (int) ($cuotas->vencido_cantidad ?? 0), 'monto' => (float) ($cuotas->vencido_monto ?? 0)],
            'pagos_por_revisar' => DB::table('payment_reports')
                ->where('estatus', 1)
                ->whereIn('payment_reports_movements_estatus_id', self::INFORME_EN_CURSO)
                ->whereIn('grupos_trabajos_user_id', $gtus)
                ->count(),
            'prestamos_por_aprobar' => $this->prestamos($gtus, $pais)
                ->where('prestamos.estatus', Prestamos::ESTATUS_PENDIENTE)
                ->where('prestamos.aprobacion_estatus_id', Prestamos::APROBACION_PENDIENTE)
                ->count(),
        ];
    }

    /**
     * @param  list<mixed>  $gtus
     * @return array<string, mixed>
     */
    private function delCliente(int $clienteId, array $gtus, mixed $pais, string $hoy): array
    {
        $cuotas = fn () => $this->cuotasPendientes($gtus, $pais)->where('prestamos.cliente_id', $clienteId);

        $resumen = $cuotas()
            ->selectRaw('coalesce(sum(d.cuota), 0) as saldo,
                coalesce(sum(case when d.date < ? then 1 else 0 end), 0) as vencidas', [$hoy])
            ->first();

        $proxima = $cuotas()->where('d.date', '>=', $hoy)->orderBy('d.date')->first(['d.date', 'd.cuota']);

        return [
            // Desde `prestamos` y no desde las cuotas: hay préstamos importados del legado sin cronograma.
            'prestamos_activos' => $this->prestamos($gtus, $pais)
                ->where('prestamos.cliente_id', $clienteId)
                ->where('prestamos.estatus', Prestamos::ESTATUS_PENDIENTE)
                ->where('prestamos.aprobacion_estatus_id', Prestamos::APROBACION_APROBADO)
                ->count(),
            'saldo_pendiente' => (float) ($resumen->saldo ?? 0),
            'proxima_cuota' => $proxima ? ['fecha' => substr((string) $proxima->date, 0, 10), 'monto' => (float) $proxima->cuota] : null,
            'cuotas_vencidas' => (int) ($resumen->vencidas ?? 0),
            'pagos_en_revision' => DB::table('payment_reports')
                ->where('estatus', 1)
                ->where('cliente_id', $clienteId)
                ->whereIn('payment_reports_movements_estatus_id', self::INFORME_EN_CURSO)
                ->whereIn('grupos_trabajos_user_id', $gtus)
                ->count(),
        ];
    }

    /** @param  list<mixed>  $gtus */
    private function prestamos(array $gtus, mixed $pais): Builder
    {
        return DB::table('prestamos')
            ->whereIn('prestamos.grupos_trabajos_user_id', $gtus)
            ->when($pais, fn (Builder $q, $p) => $q->where('prestamos.country_id', $p));
    }

    /**
     * Cuotas cobrables sin pagar de préstamos en curso y aprobados.
     *
     * @param  list<mixed>  $gtus
     */
    private function cuotasPendientes(array $gtus, mixed $pais): Builder
    {
        return $this->prestamos($gtus, $pais)
            ->join('prestamos_dias as d', 'd.prestamo_id', '=', 'prestamos.id')
            ->where('d.apply', true)
            ->where('d.pagado', false)
            ->where('prestamos.estatus', Prestamos::ESTATUS_PENDIENTE)
            ->where('prestamos.aprobacion_estatus_id', Prestamos::APROBACION_APROBADO);
    }
}
