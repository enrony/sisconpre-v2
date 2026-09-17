<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportsReport;
use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\Prestamos;
use App\Models\PrestamosEstatu;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte de préstamos (`TasksSISCONPRE.pdf` — "Reportes"): listado detallado
 * y filtrable por cliente, rango de fechas, estado, país, ciudad, grupo de
 * trabajo y responsable. Distinto de `/prestamos` (la consola operativa) —
 * este es de solo lectura, sin acciones de alta/edición.
 */
class ReportePrestamosController extends Controller
{
    use ExportsReport;

    /**
     * Clave de la fila "aplanada" (`toExportRow()`) => etiqueta de columna,
     * en el orden en que aparecen en Excel/PDF.
     *
     * @var array<string, string>
     */
    private const COLUMNAS_EXPORT = [
        'id' => '#',
        'registrado' => 'Registrado',
        'cliente' => 'Cliente',
        'documento' => 'Documento',
        'monto_prestamo' => 'Monto',
        'utilidad' => 'Utilidad',
        'total' => 'Total',
        'estado' => 'Estado',
        'pais' => 'País',
        'ciudad' => 'Ciudad',
        'grupo' => 'Grupo',
        'responsable' => 'Responsable',
    ];

    public function index(): Response
    {
        return Inertia\Inertia::render('ReportePrestamos', [
            'messages' => __('messages'),
        ]);
    }

    /**
     * Query con todos los filtros aplicados, sin columnas/eager-loads ni orden
     * — la reusan `baseQuery()` (listado) y `resumen()` (agregados), cada uno
     * agregando encima solo lo que necesita.
     *
     * @return Builder<Prestamos>
     */
    private function filteredQuery(Request $request): Builder
    {
        $csvIds = static fn (mixed $csv): array => array_values(array_filter(
            explode(',', (string) $csv),
            static fn (string $v): bool => $v !== '',
        ));

        $paisActivo = static::obtenerPaisActivo();

        return Prestamos::query()
            ->when($paisActivo, fn ($q, $pais) => $q->where('prestamos.country_id', $pais))
            ->when(! $paisActivo && $request->filled('pais'), fn ($q) => $q->where('prestamos.country_id', $request->pais))
            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($q) use ($request) {
                $q->whereBetween('prestamos.created_at', [
                    Carbon::parse($request->fecha_registro1)->startOfDay(),
                    Carbon::parse($request->fecha_registro2)->endOfDay(),
                ]);
            })
            ->when($request->filled('clientes'), fn ($q) => $q->whereIn('prestamos.cliente_id', $csvIds($request->clientes)))
            ->when($request->filled('estados'), fn ($q) => $q->whereIn('prestamos.estatus', $csvIds($request->estados)))
            ->when($request->filled('grupos'), function ($q) use ($request, $csvIds) {
                $q->whereIn('prestamos.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('idgrupo_trabajo', $csvIds($request->grupos))->pluck('id'));
            })
            ->when($request->filled('ciudad'), function ($q) use ($request) {
                $grupoIds = GruposTrabajo::where('city_id', $request->ciudad)->pluck('id');
                $q->whereIn('prestamos.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('idgrupo_trabajo', $grupoIds)->pluck('id'));
            })
            ->when($request->filled('responsables'), function ($q) use ($request, $csvIds) {
                $q->whereIn('prestamos.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('iduser', $csvIds($request->responsables))->pluck('id'));
            });
    }

    /**
     * `filteredQuery()` + columnas y eager-loads del listado detallado.
     *
     * @return Builder<Prestamos>
     */
    private function baseQuery(Request $request): Builder
    {
        return $this->filteredQuery($request)
            ->select([
                'prestamos.id',
                'prestamos.cliente_id',
                'prestamos.grupos_trabajos_user_id',
                'prestamos.country_id',
                'prestamos.monto_prestamo',
                'prestamos.tasa',
                'prestamos.utilidad',
                'prestamos.total',
                'prestamos.estatus',
                'prestamos.date_first_pay',
                'prestamos.date_last_pay',
                'prestamos.created_at',
            ])
            ->selectRaw("date_format(prestamos.created_at, '%Y-%m-%d %H:%i:%s') as created")
            ->selectRaw("json_unquote(json_extract(prestamos.cliente, '$.nombre')) as cliente_nombre")
            ->selectRaw("json_unquote(json_extract(prestamos.cliente, '$.apellido')) as cliente_apellido")
            ->selectRaw("json_unquote(json_extract(prestamos.cliente, '$.documento')) as cliente_documento")
            ->with([
                'p_estatus:id,description,type_tag',
                'country:id,Name',
                'grupoTrabajoUser.grupo_trabajo.ciudad',
                'grupoTrabajoUser.user:id,name,email',
            ])
            ->latest('prestamos.created_at');
    }

    /**
     * Vista consolidada: totales (cantidad, monto prestado, utilidad, monto
     * perdido) + desglose por estado, sobre los mismos filtros del listado.
     *
     * @return array<string, mixed>
     */
    public function resumen(Request $request): array
    {
        $base = $this->filteredQuery($request);

        // `toBase()` (query builder plano, sin hidratar a Prestamos): las
        // columnas de abajo son agregados/joins, no atributos reales del
        // modelo — igual patrón que DashboardController::carteraPorEstado().
        $totales = (clone $base)->toBase()
            ->selectRaw('count(*) as cantidad, coalesce(sum(prestamos.monto_prestamo), 0) as monto_prestado, coalesce(sum(prestamos.utilidad), 0) as utilidad')
            ->first();

        $perdidoId = PrestamosEstatu::where('description', 'Perdido')->value('id');
        $montoPerdido = $perdidoId
            ? (clone $base)->where('prestamos.estatus', $perdidoId)->sum('prestamos.monto_prestamo')
            : 0;

        $porEstado = (clone $base)->toBase()
            ->join('prestamos_estatus', 'prestamos_estatus.id', '=', 'prestamos.estatus')
            ->selectRaw('prestamos_estatus.id as estatus, prestamos_estatus.description as label, prestamos_estatus.type_tag, count(*) as cantidad')
            ->groupBy('prestamos_estatus.id', 'prestamos_estatus.description', 'prestamos_estatus.type_tag')
            ->orderBy('prestamos_estatus.id')
            ->get()
            ->map(fn (object $r): array => [
                'estatus' => (int) $r->estatus,
                'label' => (string) $r->label,
                'cantidad' => (int) $r->cantidad,
                'tipo' => json_decode((string) $r->type_tag, true)['type'] ?? 'info',
            ]);

        return [
            'totales' => [
                'cantidad' => (int) $totales->cantidad,
                'monto_prestado' => (float) $totales->monto_prestado,
                'utilidad' => (float) $totales->utilidad,
                'monto_perdido' => (float) $montoPerdido,
            ],
            'porEstado' => $porEstado->values(),
        ];
    }

    /**
     * @return array{lista: LengthAwarePaginator<int, array<string, mixed>>}
     */
    public function records(Request $request): array
    {
        $lista = $this->baseQuery($request)
            ->paginate(20)
            ->through(fn (Prestamos $r): array => $this->toRow($r));

        return compact('lista');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Prestamos $r): array
    {
        $grupo = $r->grupoTrabajoUser?->grupo_trabajo;
        $responsable = $r->grupoTrabajoUser?->user;

        return [
            'id' => $r->id,
            'cliente' => [
                'nombre' => $r->cliente_nombre,
                'apellido' => $r->cliente_apellido,
                'documento' => $r->cliente_documento,
            ],
            'created' => $r->created,
            'date_first_pay' => $r->date_first_pay,
            'date_last_pay' => $r->date_last_pay,
            'monto_prestamo' => $r->monto_prestamo,
            'tasa' => $r->tasa,
            'utilidad' => $r->utilidad,
            'total' => $r->total,
            'estado' => $r->p_estatus?->description,
            'pais' => $r->country?->Name,
            'ciudad' => $grupo?->ciudad?->nombre,
            'grupo' => $grupo?->nombre,
            'responsable' => $responsable?->name,
        ];
    }

    /**
     * Misma info que `toRow()`, pero "aplanada" para Excel/PDF (una columna
     * por dato, sin objetos anidados).
     *
     * @return array<string, mixed>
     */
    private function toExportRow(Prestamos $r): array
    {
        $grupo = $r->grupoTrabajoUser?->grupo_trabajo;
        $responsable = $r->grupoTrabajoUser?->user;

        return [
            'id' => $r->id,
            'registrado' => $r->created,
            'cliente' => trim(($r->cliente_nombre ?? '').' '.($r->cliente_apellido ?? '')),
            'documento' => $r->cliente_documento,
            'monto_prestamo' => (float) $r->monto_prestamo,
            'utilidad' => (float) $r->utilidad,
            'total' => (float) $r->total,
            'estado' => $r->p_estatus?->description,
            'pais' => $r->country?->Name,
            'ciudad' => $grupo?->ciudad?->nombre,
            'grupo' => $grupo?->nombre,
            'responsable' => $responsable?->name,
        ];
    }

    public function exportarExcel(Request $request): StreamedResponse
    {
        $filas = $this->baseQuery($request)->get()->map(fn (Prestamos $r): array => $this->toExportRow($r));

        return $this->generarExcel($filas, self::COLUMNAS_EXPORT, 'reporte_prestamos');
    }

    public function exportarPdf(Request $request): \Illuminate\Http\Response
    {
        $filas = $this->baseQuery($request)->get()->map(fn (Prestamos $r): array => $this->toExportRow($r));

        return $this->generarPdf($filas, self::COLUMNAS_EXPORT, 'reporte_prestamos', 'Reporte de préstamos', $request->boolean('inline'));
    }
}
