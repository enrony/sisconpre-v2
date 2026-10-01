<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\Prestamos;
use App\Models\PrestamosDias;
use App\Models\PrestamosEstatu;
use App\Models\TipoPrestamo;
use App\Services\AlcanceCartera;
use App\Services\PrestamoEstadoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class PrestamosController extends Controller
{
    public function __construct(private AlcanceCartera $alcance) {}

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Prestamos $prestamos)
    {
        \App::setLocale('es');

        return Inertia\Inertia::render('Prestamos',
            [
                'messages' => __('messages'),
            ]);
    }

    public function recordsEstados()
    {
        $estados = PrestamosEstatu::where('estatus', 1)->get();

        return compact('estados');
    }

    /**
     * @return array{lista: LengthAwarePaginator<int, array<string, mixed>>}
     */
    public function records(Request $request, Prestamos $Prestamos): array
    {
        $lista = $this->alcance->prestamos(Prestamos::lista($request, $Prestamos))
            ->when(
                static::obtenerPaisActivo(),
                fn ($query, $paisActivo) => $query->where('prestamos.country_id', $paisActivo),
            )
            ->paginate(10)
            ->through(fn (Prestamos $r): array => $this->toRow($r));

        return compact('lista');
    }

    /**
     * Fila del listado: exactamente lo que consume `PrestamosTable.vue`
     * (incluye `prestamos_dias` para el detalle expandible, ya sólo de la página).
     *
     * @return array<string, mixed>
     */
    private function toRow(Prestamos $r): array
    {
        return [
            'id' => $r->id,
            'cliente_id' => $r->cliente_id,
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
            'estatus' => $r->estatus,
            'pause_surcharge' => (bool) $r->pause_surcharge,
            'p_estatus' => $r->p_estatus ? [
                'id' => $r->p_estatus->id,
                'description' => $r->p_estatus->description,
                'type_tag' => $r->p_estatus->type_tag,
            ] : null,
            'aprobacion_estatus' => $r->aprobacionEstatus ? [
                'id' => $r->aprobacionEstatus->id,
                'description' => $r->aprobacionEstatus->description,
                'type_tag' => $r->aprobacionEstatus->type_tag,
            ] : null,
            'prestamos_dias' => $r->prestamos_dias->map(fn (PrestamosDias $d): array => [
                'id' => $d->id,
                'date' => $d->date,
                'sigla' => $d->sigla,
                'cuota' => $d->cuota,
                'apply' => (bool) $d->apply,
                'pagado' => (bool) $d->pagado,
                'dom' => (bool) $d->dom,
                'festivo' => (bool) $d->festivo,
            ])->all(),
        ];
    }

    public function transformData($queryAll)
    {
        return $queryAll = $queryAll->map(function ($item) {
            $item->prestamos_dias = $item->prestamos_dias->map(function ($pdias) {
                $pdias->pendientesPago2 = false;
                $pdias->payment_report_id_pendiente = null;

                $estatusReserva = $pdias->pendientesPago?->paymentReport?->payment_reports_movement_first?->estatus_description;

                if ($estatusReserva && ! $estatusReserva->finish_estatus) {
                    $pdias->pendientesPago2 = true;
                    $pdias->payment_report_id_pendiente = $pdias->pendientesPago->payment_report_id;
                }

                return $pdias;
            });

            return $item;
        });
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {

        $data = $request->all();
        $data['cliente'] = $data['clienteSelected'];
        // El cliente del préstamo es el de la ficha elegida (y tiene que ser de la cartera de quien registra).
        $data['cliente_id'] = (int) ($data['clienteSelected']['id'] ?? 0);
        $this->alcance->exigirCliente($data['cliente_id']);
        $data['form'] = null;

        $GruposTrabajoUser = $this->obtenerGrupoTrabajo();
        $data['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
        // El país del préstamo es el del grupo de trabajo activo de quien lo
        // registra, no una elección libre — igual que grupos_trabajos_user_id,
        // se fuerza acá y se ignora lo que haya mandado el frontend. Si no se
        // puede resolver (superusuario, o grupo sin ciudad todavía) se respeta
        // lo que el formulario haya enviado (ahí sí queda un selector visible).
        $data['country_id'] = static::obtenerPaisActivo() ?? $data['country_id'];
        // Todo préstamo nace pendiente de aprobación — no puede recibir un
        // pago informado hasta que alguien con `prestamos.aprobar` lo apruebe.
        $data['aprobacion_estatus_id'] = Prestamos::APROBACION_PENDIENTE;
        $Prestamos = Prestamos::create($data);

        // $PrestamosDias = new PrestamosDias();
        // $PrestamosDiasFillable = $PrestamosDias->getFillable();

        $cuotas = [];

        collect($data['list_pays'])->map(function ($lista) use (&$cuotas) {

            // if($data['apply_surcharge'] == 'true'){
            //     $lista['apply_surcharge'] = true;
            //     $lista['surcharge'] = $data['surcharge'];
            //     $lista['days_apply_surcharge'] = $data['days_apply_surcharge'];
            // }

            $PrestamosDias = new PrestamosDias;
            $PrestamosDias->fill($lista);
            array_push($cuotas, $PrestamosDias);
        });

        $Prestamos->prestamos_dias()->saveMany($cuotas);

        session()->flash('flash.message', 'Registro creado!');

        session()->flash('flash.type', 'success');

        return Redirect::route('prestamos', ['page' => $request->input('paginaActual')]);

    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Prestamos $prestamos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Prestamos $prestamos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Prestamos $prestamos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Prestamos $prestamos)
    {
        //
    }

    public function tables(TipoPrestamo $TipoPrestamo)
    {

        $TipoPrestamo = $TipoPrestamo->get();
        $CountryHoliday = CountryHoliday::CriterioYear()->get();

        // El país del préstamo lo determina el grupo de trabajo activo de
        // quien lo registra (Controller::obtenerPaisActivo()), no la lista de
        // países en los que puede FUNDAR un grupo (eso es user_countries, un
        // concepto distinto). Si no se puede resolver (superusuario, o un
        // grupo sin ciudad todavía) se deja elegir entre todos los activos.
        $paisActivo = static::obtenerPaisActivo();
        $CountryAll = $paisActivo
            ? Country::where('id', $paisActivo)->where('estatus', 1)->get()
            : Country::where('estatus', 1)->get();

        return compact('TipoPrestamo', 'CountryHoliday', 'CountryAll');
    }

    public function obtenerPrestamosActivos(Prestamos $prestamos, $cliente)
    {
        $this->alcance->exigirCliente((int) $cliente);

        return $this->transformData($prestamos->with(['prestamos_dias' => function ($prestamos) {
            $prestamos->with(['pendientesPago']);
        }])->select('*', 'created_at as created')->PrestamoActivo($cliente)->get());
    }

    /**
     * PDF tipo "cartón" para entregar/compartir con el cliente: sus datos,
     * los del préstamo, y el cronograma de cuotas con el saldo restante
     * planificado ("Resta") tras cada una — no el saldo real post-pago, que
     * ya se ve en `Estado` (Pagada/Vencida/Pendiente).
     */
    public function imprimirCarton(Prestamos $prestamo): Response
    {
        $this->alcance->exigirPrestamo($prestamo);

        $prestamo->load([
            'datoCliente',
            'p_estatus',
            'prestamos_dias' => fn ($query) => $query->orderBy('date'),
        ]);

        $saldo = (float) $prestamo->total;

        $cuotas = $prestamo->prestamos_dias->map(function (PrestamosDias $dia) use (&$saldo): array {
            $fecha = Carbon::parse($dia->date);

            if ($dia->apply) {
                $saldo -= (float) $dia->cuota;
            }

            return [
                'fecha' => $fecha->format('d/m/Y'),
                'aplica' => (bool) $dia->apply,
                'cuota' => (float) $dia->cuota,
                'estado' => match (true) {
                    ! $dia->apply => 'No aplica',
                    (bool) $dia->pagado => 'Pagada',
                    $fecha->lt(now()->startOfDay()) => 'Vencida',
                    default => 'Pendiente',
                },
                'resta' => $dia->apply ? $saldo : null,
            ];
        });

        $pdf = Pdf::loadView('prestamos.carton', [
            'prestamo' => $prestamo,
            'cliente' => $prestamo->datoCliente,
            'cuotas' => $cuotas,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("carton-prestamo-{$prestamo->id}.pdf");
    }

    /**
     * Aprueba o rechaza el préstamo: mientras no esté aprobado, no puede
     * recibir un pago informado (`PaymentReportService::verifiedPrestamosAprobados()`,
     * y `scopePrestamoActivo()` lo oculta de "Informar un pago").
     */
    /** @return array{success: bool, aprobacion_estatus_id: int, message: string} */
    public function aprobar(Prestamos $prestamo): array
    {
        return $this->cambiarAprobacion($prestamo, Prestamos::APROBACION_APROBADO, 'Préstamo aprobado');
    }

    /** @return array{success: bool, aprobacion_estatus_id: int, message: string} */
    public function rechazar(Prestamos $prestamo): array
    {
        return $this->cambiarAprobacion($prestamo, Prestamos::APROBACION_RECHAZADO, 'Préstamo rechazado');
    }

    /**
     * La aprobación solo se decide mientras el préstamo está en curso: un
     * préstamo pagado, anulado o perdido ya cerró ese tema.
     *
     * @param  Prestamos::APROBACION_*  $aprobacion
     * @return array{success: bool, aprobacion_estatus_id: int, message: string}
     */
    private function cambiarAprobacion(Prestamos $prestamo, int $aprobacion, string $mensaje): array
    {
        $this->alcance->exigirPrestamo($prestamo);

        if ((int) $prestamo->estatus !== Prestamos::ESTATUS_PENDIENTE) {
            return [
                'success' => false,
                'aprobacion_estatus_id' => $prestamo->aprobacion_estatus_id,
                'message' => 'El préstamo ya no está en curso: no se puede cambiar su aprobación.',
            ];
        }

        $prestamo->aprobacion_estatus_id = $aprobacion;
        $prestamo->save();

        return [
            'success' => true,
            'aprobacion_estatus_id' => $prestamo->aprobacion_estatus_id,
            'message' => $mensaje,
        ];
    }

    /** @return array{success: bool, message: string} */
    public function anular(Request $request, Prestamos $prestamo, PrestamoEstadoService $estados): array
    {
        $this->alcance->exigirPrestamo($prestamo);
        $motivo = $this->motivoRequerido($request);

        return $this->cambiarEstado(fn () => $estados->anular($prestamo, $motivo), 'Préstamo anulado');
    }

    /** @return array{success: bool, message: string} */
    public function marcarPerdido(Request $request, Prestamos $prestamo, PrestamoEstadoService $estados): array
    {
        $this->alcance->exigirPrestamo($prestamo);
        $motivo = $this->motivoRequerido($request);

        return $this->cambiarEstado(fn () => $estados->marcarPerdido($prestamo, $motivo), 'Préstamo marcado como perdido');
    }

    /** @return array{success: bool, message: string} */
    public function reactivar(Request $request, Prestamos $prestamo, PrestamoEstadoService $estados): array
    {
        $this->alcance->exigirPrestamo($prestamo);
        $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);
        $motivo = $request->filled('motivo') ? $request->string('motivo')->trim()->toString() : null;

        return $this->cambiarEstado(fn () => $estados->reactivar($prestamo, $motivo), 'Préstamo reactivado');
    }

    private function motivoRequerido(Request $request): string
    {
        $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        return $request->string('motivo')->trim()->toString();
    }

    /**
     * @param  callable(): void  $accion
     * @return array{success: bool, message: string}
     */
    private function cambiarEstado(callable $accion, string $mensaje): array
    {
        try {
            DB::transaction(fn () => $accion());
        } catch (DomainException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => $mensaje];
    }

    /**
     * Pausa / reanuda la aplicación del recargo por mora del préstamo.
     * `pause_surcharge` lo respeta el comando `prestamos:aplicar-recargos`.
     */
    public function togglePausaRecargo(Prestamos $prestamo)
    {
        $this->alcance->exigirPrestamo($prestamo);
        $prestamo->pause_surcharge = ! $prestamo->pause_surcharge;
        $prestamo->save();

        return [
            'success' => true,
            'pause_surcharge' => (bool) $prestamo->pause_surcharge,
            'message' => $prestamo->pause_surcharge
                ? 'Recargo por mora pausado'
                : 'Recargo por mora reanudado',
        ];
    }
}
