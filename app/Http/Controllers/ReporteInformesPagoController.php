<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportsReport;
use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\PaymentReport;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte de informes de pago recibidos: listado detallado y filtrable por
 * cliente, rango de fechas, estado, destino (cuotas/saldo a favor), método
 * de pago, banco, país, ciudad, grupo de trabajo y responsable. Distinto de
 * `/payment_report` (la consola operativa) — este es de solo lectura, sin
 * acciones de cambio de estado.
 */
class ReporteInformesPagoController extends Controller
{
    use ExportsReport;

    /**
     * @var array<string, string>
     */
    private const COLUMNAS_EXPORT = [
        'id' => '#',
        'registrado' => 'Registrado',
        'cliente' => 'Cliente',
        'documento' => 'Documento',
        'destino' => 'Destino',
        'monto' => 'Monto',
        'estado' => 'Estado',
        'metodos' => 'Métodos de pago',
        'bancos' => 'Bancos',
        'pais' => 'País',
        'ciudad' => 'Ciudad',
        'grupo' => 'Grupo',
        'responsable' => 'Responsable',
    ];

    public function index(): Response
    {
        return Inertia\Inertia::render('ReporteInformesPago', [
            'messages' => __('messages'),
        ]);
    }

    /**
     * Query filtrada, sin paginar — la reusan tanto `records()` (paginada)
     * como las exportaciones (traen todo lo filtrado, no solo la página
     * visible).
     *
     * @return Builder<PaymentReport>
     */
    private function baseQuery(Request $request): Builder
    {
        $csvIds = static fn (mixed $csv): array => array_values(array_filter(
            explode(',', (string) $csv),
            static fn (string $v): bool => $v !== '',
        ));

        $paisActivo = static::obtenerPaisActivo();

        // El vínculo a país es indirecto (payment_reports no tiene country_id
        // propio): selected_payment_reports -> prestamos_dias -> prestamos.
        // Un informe de "saldo a favor" (sin cuotas) no tiene país resoluble
        // -> queda siempre visible en vez de ocultarse (mismo criterio que
        // PaymentReportController::records()).
        $porPais = fn ($q, string $pais) => $q->where(
            fn ($qq) => $qq->whereDoesntHave('selected_payment_reports')
                ->orWhereHas(
                    'selected_payment_reports.PrestamosDias.Prestamo',
                    fn ($q2) => $q2->where('country_id', $pais),
                ),
        );

        return PaymentReport::query()
            ->select([
                'payment_reports.id',
                'payment_reports.cliente_id',
                'payment_reports.grupos_trabajos_user_id',
                'payment_reports.destination',
                'payment_reports.importe',
                'payment_reports.created_at',
            ])
            ->selectRaw("date_format(payment_reports.created_at, '%Y-%m-%d %H:%i:%s') as created")
            ->selectRaw("json_unquote(json_extract(payment_reports.cliente, '$.nombre')) as cliente_nombre")
            ->selectRaw("json_unquote(json_extract(payment_reports.cliente, '$.apellido')) as cliente_apellido")
            ->selectRaw("json_unquote(json_extract(payment_reports.cliente, '$.documento')) as cliente_documento")
            ->where('payment_reports.estatus', 1)
            ->withSum('payment_reports_methods as value_amount_sum', 'importe')
            ->with([
                'payment_reports_movement_first.estatus_description',
                'grupoTrabajoUser.grupo_trabajo.ciudad',
                'grupoTrabajoUser.user:id,name,email',
                'payment_reports_methods.paymentMethod:id,description',
                'payment_reports_methods.bank:id,description',
                'selected_payment_reports.PrestamosDias.Prestamo.country:id,Name',
            ])
            ->when($paisActivo, $porPais)
            ->when(! $paisActivo && $request->filled('pais'), fn ($q) => $porPais($q, $request->string('pais')->toString()))
            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($q) use ($request) {
                $q->whereBetween('payment_reports.created_at', [
                    Carbon::parse($request->fecha_registro1)->startOfDay(),
                    Carbon::parse($request->fecha_registro2)->endOfDay(),
                ]);
            })
            ->when($request->filled('clientes'), fn ($q) => $q->whereIn('payment_reports.cliente_id', $csvIds($request->clientes)))
            ->when($request->filled('estados'), function ($q) use ($request, $csvIds) {
                $q->whereHas('payment_reports_movement_first', fn ($q2) => $q2->whereIn('estatus', $csvIds($request->estados)));
            })
            ->when($request->filled('destinos'), fn ($q) => $q->whereIn('payment_reports.destination', $csvIds($request->destinos)))
            ->when($request->filled('metodos'), function ($q) use ($request, $csvIds) {
                $q->whereHas('payment_reports_methods', fn ($q2) => $q2->whereIn('payment_method_id', $csvIds($request->metodos)));
            })
            ->when($request->filled('bancos'), function ($q) use ($request, $csvIds) {
                $q->whereHas('payment_reports_methods', fn ($q2) => $q2->whereIn('bank_id', $csvIds($request->bancos)));
            })
            ->when($request->filled('grupos'), function ($q) use ($request, $csvIds) {
                $q->whereIn('payment_reports.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('idgrupo_trabajo', $csvIds($request->grupos))->pluck('id'));
            })
            ->when($request->filled('ciudad'), function ($q) use ($request) {
                $grupoIds = GruposTrabajo::where('city_id', $request->ciudad)->pluck('id');
                $q->whereIn('payment_reports.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('idgrupo_trabajo', $grupoIds)->pluck('id'));
            })
            ->when($request->filled('responsables'), function ($q) use ($request, $csvIds) {
                $q->whereIn('payment_reports.grupos_trabajos_user_id', GruposTrabajoUser::whereIn('iduser', $csvIds($request->responsables))->pluck('id'));
            })
            ->latest('payment_reports.created_at');
    }

    /**
     * @return array{lista: LengthAwarePaginator<int, array<string, mixed>>}
     */
    public function records(Request $request): array
    {
        $lista = $this->baseQuery($request)
            ->paginate(20)
            ->through(fn (PaymentReport $r): array => $this->toRow($r));

        return compact('lista');
    }

    /** País del informe: el de la primera cuota vinculada, si tiene. */
    private function pais(PaymentReport $r): ?string
    {
        return $r->selected_payment_reports->first()?->PrestamosDias?->Prestamo?->country?->Name;
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(PaymentReport $r): array
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
            'destination' => $r->destination,
            'destination_text' => $r->destination == 1 ? 'Pago de cuota(s)' : 'Saldo a favor',
            'monto' => (float) ($r->value_amount_sum ?? 0),
            'estado' => $r->payment_reports_movement_first?->estatus_description?->description,
            'metodos' => $r->payment_reports_methods->pluck('paymentMethod.description')->filter()->unique()->values(),
            'bancos' => $r->payment_reports_methods->pluck('bank.description')->filter()->unique()->values(),
            'pais' => $this->pais($r),
            'ciudad' => $grupo?->ciudad?->nombre,
            'grupo' => $grupo?->nombre,
            'responsable' => $r->grupoTrabajoUser?->user?->name,
        ];
    }

    /**
     * Misma info que `toRow()`, aplanada para Excel/PDF.
     *
     * @return array<string, mixed>
     */
    private function toExportRow(PaymentReport $r): array
    {
        $grupo = $r->grupoTrabajoUser?->grupo_trabajo;

        return [
            'id' => $r->id,
            'registrado' => $r->created,
            'cliente' => trim(($r->cliente_nombre ?? '').' '.($r->cliente_apellido ?? '')),
            'documento' => $r->cliente_documento,
            'destino' => $r->destination == 1 ? 'Pago de cuota(s)' : 'Saldo a favor',
            'monto' => (float) ($r->value_amount_sum ?? 0),
            'estado' => $r->payment_reports_movement_first?->estatus_description?->description,
            'metodos' => $r->payment_reports_methods->pluck('paymentMethod.description')->filter()->unique()->implode(', '),
            'bancos' => $r->payment_reports_methods->pluck('bank.description')->filter()->unique()->implode(', '),
            'pais' => $this->pais($r),
            'ciudad' => $grupo?->ciudad?->nombre,
            'grupo' => $grupo?->nombre,
            'responsable' => $r->grupoTrabajoUser?->user?->name,
        ];
    }

    public function exportarExcel(Request $request): StreamedResponse
    {
        $filas = $this->baseQuery($request)->get()->map(fn (PaymentReport $r): array => $this->toExportRow($r));

        return $this->generarExcel($filas, self::COLUMNAS_EXPORT, 'reporte_informes_pago');
    }

    public function exportarPdf(Request $request): \Illuminate\Http\Response
    {
        $filas = $this->baseQuery($request)->get()->map(fn (PaymentReport $r): array => $this->toExportRow($r));

        return $this->generarPdf($filas, self::COLUMNAS_EXPORT, 'reporte_informes_pago', 'Reporte de informes de pago', $request->boolean('inline'));
    }
}
