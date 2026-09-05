<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentReportRequest;
use App\Http\Requests\UpdatePaymentReportRequest;
use App\Models\PaymentReport;
use App\Models\Prestamos;
use App\Services\PaymentReportService;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia;
use Symfony\Component\HttpFoundation\Request;

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

    public function records(Request $request, PaymentReport $PaymentReport)
    {

        $lista = $this->paginate($PaymentReport::lista($request, $PaymentReport));

        return compact('lista');
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

    public function paginate($queryAll, $per_page = 15, $page = null, $options = [])
    {

        $queryAll = $queryAll->map(function ($item) {
            $item->estatus_selected = null;
            $item->support_image = [];
            $item->motivo = null;

            return $item;
        });

        $collection = new Collection($queryAll);
        $page = Paginator::resolveCurrentPage() ?: 1;
        $per_page = 100;
        $currentPageResults = $collection->slice(($page - 1) * $per_page, $per_page)->values();

        return new LengthAwarePaginator($currentPageResults, count($collection), $per_page);
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
