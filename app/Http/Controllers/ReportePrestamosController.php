<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\Prestamos;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Inertia;
use Inertia\Response;

/**
 * Reporte de préstamos (`TasksSISCONPRE.pdf` — "Reportes"): listado detallado
 * y filtrable por cliente, rango de fechas, país, ciudad y grupo de trabajo.
 * Distinto de `/prestamos` (la consola operativa) — este es de solo lectura,
 * sin acciones de alta/edición.
 */
class ReportePrestamosController extends Controller
{
    public function index(): Response
    {
        return Inertia\Inertia::render('ReportePrestamos', [
            'messages' => __('messages'),
        ]);
    }

    /**
     * @return array{lista: LengthAwarePaginator<int, array<string, mixed>>}
     */
    public function records(Request $request): array
    {
        $csvIds = static fn (mixed $csv): array => array_values(array_filter(
            explode(',', (string) $csv),
            static fn (string $v): bool => $v !== '',
        ));

        $paisActivo = static::obtenerPaisActivo();

        $lista = Prestamos::query()
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
            ->with(['p_estatus:id,description,type_tag', 'country:id,Name', 'grupoTrabajoUser.grupo_trabajo.ciudad'])
            ->when($paisActivo, fn ($q, $pais) => $q->where('prestamos.country_id', $pais))
            ->when(! $paisActivo && $request->filled('pais'), fn ($q) => $q->where('prestamos.country_id', $request->pais))
            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($q) use ($request) {
                $q->whereBetween('prestamos.created_at', [
                    Carbon::parse($request->fecha_registro1)->startOfDay(),
                    Carbon::parse($request->fecha_registro2)->endOfDay(),
                ]);
            })
            ->when($request->filled('clientes'), fn ($q) => $q->whereIn('prestamos.cliente_id', $csvIds($request->clientes)))
            ->when($request->filled('grupo'), function ($q) use ($request) {
                $q->whereIn('prestamos.grupos_trabajos_user_id', GruposTrabajoUser::where('idgrupo_trabajo', $request->grupo)->pluck('id'));
            })
            ->when($request->filled('ciudad'), function ($q) use ($request) {
                $grupoIds = GruposTrabajo::where('city_id', $request->ciudad)->pluck('id');
                $q->whereIn('prestamos.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('idgrupo_trabajo', $grupoIds)->pluck('id'));
            })
            ->latest('prestamos.created_at')
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
        ];
    }

    /**
     * Datos para los selects de filtro: países/ciudades/grupos ya acotados al
     * país activo del usuario (igual criterio que `PrestamosController::tables()`).
     *
     * @return array<string, mixed>
     */
    public function tables(): array
    {
        $paisActivo = static::obtenerPaisActivo();

        $CountryAll = $paisActivo
            ? Country::where('id', $paisActivo)->where('estatus', 1)->get()
            : Country::where('estatus', 1)->get();

        $countryIds = $CountryAll->pluck('id')->toArray();

        $CitiesAll = City::WhereCountriesActive($countryIds)->get();
        $GruposTrabajoAll = GruposTrabajo::whereIn('city_id', $CitiesAll->pluck('id'))->where('estatus', 1)->get();

        return compact('CountryAll', 'CitiesAll', 'GruposTrabajoAll');
    }
}
