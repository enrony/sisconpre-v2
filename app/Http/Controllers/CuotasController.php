<?php

namespace App\Http\Controllers;

use App\Models\Prestamos;
use App\Services\AlcanceCartera;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

/**
 * Cuotas pendientes: qué tiene que cobrar el prestamista (su grupo) o qué
 * tiene que pagar el cliente (lo suyo) — el alcance lo decide
 * `AlcanceCartera`. Por defecto, las de hoy más las vencidas.
 *
 * Cuentan las cuotas que se cobran (`apply`), no pagadas, de préstamos en
 * curso y aprobados. Una cuota con un pago informado sin resolver no
 * desaparece: se marca "en revisión" con su nº de informe.
 */
class CuotasController extends Controller
{
    /** `payment_reports_movements_estatus`: Pendiente y En revisión. */
    private const INFORME_EN_CURSO = [1, 4];

    public function __construct(private AlcanceCartera $alcance) {}

    public function index(): Response
    {
        return Inertia::render('Cuotas', [
            'esCliente' => $this->alcance->esCliente(),
        ]);
    }

    /**
     * @return array{
     *     lista: LengthAwarePaginator<int, array<string, mixed>>,
     *     totales: array<string, array{cantidad: int, monto: float}>,
     * }
     */
    public function records(Request $request): array
    {
        $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'vencidas' => ['nullable', 'boolean'],
            'clientes' => ['nullable', 'string'],
        ]);

        $hoy = CarbonImmutable::today()->toDateString();
        $desde = $request->string('desde')->toString() ?: $hoy;
        $hasta = $request->string('hasta')->toString() ?: $desde;

        if ($hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        // Las vencidas son las anteriores al rango (y a hoy): las "demoradas".
        $limiteVencidas = min($desde, $hoy);
        $conVencidas = $request->boolean('vencidas', true);

        $base = $this->base($request)->where(fn (Builder $q) => $q
            ->whereBetween('d.date', [$desde, $hasta])
            ->when($conVencidas, fn (Builder $q2) => $q2->orWhere('d.date', '<', $limiteVencidas)));

        $totales = (clone $base)->selectRaw(
            'coalesce(sum(case when d.date < ? then d.cuota else 0 end), 0) as vencido_monto,
             coalesce(sum(case when d.date < ? then 1 else 0 end), 0) as vencido_cantidad,
             coalesce(sum(case when d.date = ? then d.cuota else 0 end), 0) as hoy_monto,
             coalesce(sum(case when d.date = ? then 1 else 0 end), 0) as hoy_cantidad,
             coalesce(sum(case when d.date > ? then d.cuota else 0 end), 0) as proximo_monto,
             coalesce(sum(case when d.date > ? then 1 else 0 end), 0) as proximo_cantidad',
            [$hoy, $hoy, $hoy, $hoy, $hoy, $hoy],
        )->first();

        $enCurso = implode(',', self::INFORME_EN_CURSO);

        $lista = $base
            ->select(['d.id', 'd.date', 'd.sigla', 'd.cuota', 'prestamos.id as prestamo_id', 'c.id as cliente_id', 'c.telefono', 'c.direccion'])
            ->selectRaw("concat_ws(' ', c.nombre, c.apellido) as cliente")
            ->selectRaw("(select max(spr.payment_report_id) from selected_payment_reports spr
                join payment_reports pr on pr.id = spr.payment_report_id
                where spr.prestamos_dia_id = d.id and pr.payment_reports_movements_estatus_id in ({$enCurso})) as informe_en_revision")
            ->orderBy('d.date')
            ->orderBy('c.nombre')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (stdClass $r): array => $this->toRow($r, $hoy));

        return [
            'lista' => $lista,
            'totales' => [
                'vencido' => ['cantidad' => (int) ($totales->vencido_cantidad ?? 0), 'monto' => (float) ($totales->vencido_monto ?? 0)],
                'hoy' => ['cantidad' => (int) ($totales->hoy_cantidad ?? 0), 'monto' => (float) ($totales->hoy_monto ?? 0)],
                'proximo' => ['cantidad' => (int) ($totales->proximo_cantidad ?? 0), 'monto' => (float) ($totales->proximo_monto ?? 0)],
            ],
        ];
    }

    private function base(Request $request): Builder
    {
        $esCliente = $this->alcance->esCliente();

        $query = DB::table('prestamos_dias as d')
            ->join('prestamos', 'prestamos.id', '=', 'd.prestamo_id')
            ->join('clientes as c', 'c.id', '=', 'prestamos.cliente_id')
            ->where('d.apply', true)
            ->where('d.pagado', false)
            ->where('prestamos.estatus', Prestamos::ESTATUS_PENDIENTE)
            ->where('prestamos.aprobacion_estatus_id', Prestamos::APROBACION_APROBADO)
            // El personal opera en su país activo; el cliente ve todas sus cuotas.
            ->when(! $esCliente ? static::obtenerPaisActivo() : null, fn (Builder $q, string $pais) => $q->where('prestamos.country_id', $pais))
            ->when(! $esCliente && $request->filled('clientes'), fn (Builder $q) => $q->whereIn(
                'prestamos.cliente_id',
                array_values(array_filter(explode(',', $request->string('clientes')->toString()))),
            ));

        return $this->alcance->prestamos($query);
    }

    /** @return array<string, mixed> */
    private function toRow(stdClass $r, string $hoy): array
    {
        $fecha = substr((string) $r->date, 0, 10);

        return [
            'id' => (int) $r->id,
            'fecha' => $fecha,
            'sigla' => (string) $r->sigla,
            'monto' => (float) $r->cuota,
            'estado' => $fecha < $hoy ? 'vencida' : ($fecha === $hoy ? 'hoy' : 'proxima'),
            'dias_vencida' => $fecha < $hoy ? (int) CarbonImmutable::parse($fecha)->diffInDays($hoy) : 0,
            'informe_en_revision' => $r->informe_en_revision ? (int) $r->informe_en_revision : null,
            'prestamo_id' => (int) $r->prestamo_id,
            'cliente_id' => (int) $r->cliente_id,
            'cliente' => (string) $r->cliente,
            'telefono' => $r->telefono ? (string) $r->telefono : null,
            'direccion' => $r->direccion ? (string) $r->direccion : null,
        ];
    }
}
