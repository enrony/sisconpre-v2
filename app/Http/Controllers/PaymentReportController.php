<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentReportRequest;
use App\Http\Requests\UpdatePaymentReportRequest;
use App\Models\PaymentReport;
use App\Models\Prestamos;
use App\Services\PaymentReportService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia;

class PaymentReportController extends Controller
{
    private PaymentReportService $PaymentReportService;

    public function __construct(
        PaymentReportService $PaymentReportService
    ) {
        $this->PaymentReportService = $PaymentReportService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, PaymentReport $PaymentReport)
    {
        // dd(55);
        return Inertia\Inertia::render(
            'PaymentReport',
            [
                'messages' => __('messages'),
            ]
        );
    }

    /**
     * @return array{lista: LengthAwarePaginator<int, array<string, mixed>>}
     */
    public function records(Request $request, PaymentReport $PaymentReport): array
    {
        $lista = $PaymentReport::lista($request, $PaymentReport)
            ->paginate(100)
            ->through(fn (PaymentReport $r): array => $this->toRow($r));

        return compact('lista');
    }

    /**
     * Fila del listado: exactamente lo que consume `PaymentReportTable.vue`.
     *
     * @return array<string, mixed>
     */
    private function toRow(PaymentReport $r): array
    {
        return [
            'id' => $r->id,
            'cliente_id' => $r->cliente_id,
            'cliente' => [
                'id' => $r->cliente_id,
                'nombre' => $r->cliente_nombre,
                'apellido' => $r->cliente_apellido,
                'documento' => $r->cliente_documento,
            ],
            'destination' => $r->destination,
            'destination_text' => $r->destination == 1 ? 'Pago de cuota(s)' : 'Saldo a favor',
            'approved' => $r->approved,
            'payment_report_id' => $r->payment_report_id,
            'motivo' => $r->motivo,
            'importe' => $r->importe,
            'estatus' => $r->estatus,
            'payment_reports_movements_estatus_id' => $r->payment_reports_movements_estatus_id,
            'created' => optional($r->created_at)->format('Y-m-d H:i:s'),
            'updated' => optional($r->updated_at)->format('Y-m-d H:i:s'),
            'number_cuotas' => (int) $r->selected_payment_reports_count,
            'value_amount' => (float) ($r->value_amount_sum ?? 0),
            'status_description' => $this->statusDescription($r),
            'estatus_selected' => null,
            'support_image' => [],
        ];
    }

    /**
     * Último estado del informe, con el mismo shape que devolvía el accessor
     * `status_description` del modelo (id, desc, color, finish_estatus).
     *
     * @return array{id:int, desc:string, color:string, finish_estatus:bool}
     */
    private function statusDescription(PaymentReport $r): array
    {
        $desc = $r->payment_reports_movement_first?->estatus_description;

        if ($desc) {
            return [
                'id' => (int) $desc->id,
                'desc' => (string) $desc->description,
                'color' => (string) $desc->style,
                'finish_estatus' => (bool) $desc->finish_estatus,
            ];
        }

        return ['id' => 1, 'desc' => 'Pendiente', 'color' => 'text-gray-400', 'finish_estatus' => false];
    }

    public function record($id)
    {

        // 1 y 2. Eager loading anidado y manejador de fallos
        $paymentReport = PaymentReport::with('selected_payment_reports.PrestamosDias')->findOrFail($id);

        // 3. Uso directo de colecciones de Eloquent sin "collect()"
        $idPrestamos = $paymentReport->selected_payment_reports
            ->pluck('PrestamosDias.prestamo_id')
            ->unique()
            ->toArray();

        $idCuotasSelected = $paymentReport->selected_payment_reports
            ->pluck('prestamos_dia_id')
            ->toArray();

        // 4 y 5. Manipulación directa de propiedades
        $prestamos = Prestamos::with('prestamos_dias')
            ->whereIn('id', $idPrestamos)
            ->get()
            ->map(function ($prestamo) use ($idCuotasSelected) {

                $prestamo->prestamos_dias->transform(function ($pd) use ($idCuotasSelected) {
                    // Evitamos repetir in_array guardándolo en una variable
                    $esSeleccionado = in_array($pd->id, $idCuotasSelected);

                    $pd->p_seleccionado = $esSeleccionado;
                    $pd->textColorCuotas = $esSeleccionado ? 'text-blue-500' : '';

                    return $pd;
                });

                return $prestamo;
            });

        // Se preserva la respuesta en array asoc (es igual a hacer un compact pero sin atarse al nombre local)
        return ['Prestamos' => $prestamos];
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
     * @param  StorePaymentReportRequest  $request
     * @return Response
     */
    // public function store(StorePaymentReportRequest $request)
    public function change_estatus_report(Request $request)
    {
        DB::beginTransaction();
        try {

            // Parseamos la data recibida desde el Front
            $this->PaymentReportService->parseaRequest($request);

            // Verificamos estado del documento por el cual se va a cambiar
            $this->PaymentReportService->verifiedCurrentStatus();

            // Obtiene los datos del reporte de pago seleccionado
            $this->PaymentReportService->changeStatePaymentReport();

            // Verificamos si fue aprobado para registro de saldo a favor y procesamiento de cuotas seleccionadas
            $this->PaymentReportService->processReportPaymentAproved();

            // Registro imagen de soporte
            $this->PaymentReportService->registerSupportChangeState();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Proceso realizado con éxito',
        ];
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            // Parseamos la data recibida desde el Front
            $this->PaymentReportService->parseaRequest($request);

            // Registra encabezado del pago reportado
            $this->PaymentReportService->createPaymentReport($request);

            // Registrar metodos de pagos
            $this->PaymentReportService->registerPaymentMehods();

            // Registrar estado inicial del informe de pago
            $this->PaymentReportService->registerPaymentMovement();

            // Registrar soporte para metodos de pagos
            $this->PaymentReportService->registerSupportPaymentMehods();

            // Registro de cuotas seleccionadas para pagar
            $this->PaymentReportService->registerPaymentCuotas();

            // Registro de saldo a favor
            $this->PaymentReportService->registerPositiveBalance();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Falló el registro del pago, intente nuevamente!!!'.$e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Pago registrado con éxito',
        ];
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(PaymentReport $paymentReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PaymentReport $paymentReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatePaymentReportRequest $request, PaymentReport $paymentReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(PaymentReport $paymentReport)
    {
        //
    }

    public function obtenerReportPaymentActivos(PaymentReport $PaymentReport, $cliente)
    {

        $table = 'payment_reports';

        return $PaymentReport->selectRaw("
            {$table}.id, 
            {$table}.cliente_id, 
            {$table}.cliente, 
            {$table}.destination, 
            {$table}.type_payment_record_id, 
            {$table}.grupos_trabajos_user_id, 
            {$table}.approved, 
            {$table}.payment_report_id, 
            {$table}.motivo, 
            {$table}.estatus, 
            {$table}.importe, 
            {$table}.payment_reports_movements_estatus_id, 
            date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, 
            date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated
        ")
            ->with(['payment_reports_movement_first'])
            ->PaymentReportActivo($cliente)->get()->map(function ($item) {
                $item->estatus_selected = null;
                $item->support_image = [];
                $item->motivo = null;

                // if( in_array($item->payment_reports_movement[0]->estatus, [1,4]) ){
                return $item;
                // }
            })->filter(function ($item) {
                if (in_array($item->payment_reports_movement[0]->estatus, [1, 4])) {
                    return $item;
                }
            })->values();
    }
}
