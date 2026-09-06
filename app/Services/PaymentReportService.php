<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\CustomerMovementHistory;
use App\Models\PaymentReport;
use App\Models\PaymentReportsMovement;
use App\Models\PaymentReportsSupportMovement;
use App\Models\PrestamosDias;
use App\Models\SelectedPaymentReport;
use App\Models\SummaryCustomerMovement;
use App\Models\supportPaymentReportsMethod;
use App\Models\TypePaymentRecord;
use App\Models\TypesMovement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;

class PaymentReportService
{
    public $data;

    public $PaymentReport;

    public $PaymentReportsMovement;

    public $grupos_trabajos_user_id;

    public $prestamosIdNotProccess;

    public function __construct()
    {
        $this->prestamosIdNotProccess = [];
    }

    public function parseaRequest(Request $request)
    {

        $this->data = $request->all();

        $this->data = json_decode($this->data['data'], true);

        $GruposTrabajoUser = Controller::obtenerGrupoTrabajo();
        $this->grupos_trabajos_user_id = $GruposTrabajoUser->id;
    }

    public function verifiedCurrentStatus()
    {
        // 1. Usamos findOrFail() para evitar errores si el reporte no existe y lanzar ModelNotFoundException
        // 2. Removemos la carga pesada de 'selected_payment_reports' ya que no se usa aquí. Se cargará de forma diferida (lazy load) en processReportPaymentAproved solo si es necesario.
        $this->PaymentReport = PaymentReport::with('payment_reports_movement_first')->findOrFail($this->data['id']);

        // 3. Obtenemos el movimiento y evitamos errores (Notice/Error) si resultase null
        $movement = $this->PaymentReport->payment_reports_movement_first;

        if ($movement && $movement->estatus == $this->data['estatus_selected']) {
            throw new \Exception('El estado seleccionado para el reporte indicado ya fue procesado anteriormente, actualice la pagina.');
        }
    }

    public function changeStatePaymentReport()
    {

        $GruposTrabajoUser = Controller::obtenerGrupoTrabajo();
        $this->grupos_trabajos_user_id = $GruposTrabajoUser->id;

        $this->PaymentReport->payment_reports_movements_estatus_id = $this->data['estatus_selected'];
        $this->PaymentReport->save();

        $this->registerPaymentMovement($this->data['estatus_selected'], $this->data['motivo']);
    }

    public function createPaymentReport(Request $request)
    {

        $this->data['grupos_trabajos_user_id'] = $this->grupos_trabajos_user_id;
        $this->data['type_payment_record_id'] = TypePaymentRecord::first()->id;
        $this->data['destination'] = $this->data['tipoPago'];
        $this->PaymentReport = PaymentReport::create($this->data);
    }

    public function registerPaymentMovement($state_selected = 1, $motivo = null)
    {

        $PaymentReportsMovement = new PaymentReportsMovement;
        $PaymentReportsMovement->grupos_trabajos_user_id = $this->grupos_trabajos_user_id;
        $PaymentReportsMovement->motivo = $motivo;
        $PaymentReportsMovement->estatus = $state_selected;
        $this->PaymentReport->payment_reports_movement()->saveMany([$PaymentReportsMovement]);

        $this->PaymentReportsMovement = $PaymentReportsMovement;
    }

    public function registerPaymentMehods()
    {
        $methodsData = array_map(function ($lista) {
            $lista['importe'] = $lista['valor_importe'];

            return $lista;
        }, $this->data['dataPayments']);

        $this->PaymentReport->payment_reports_methods()->createMany($methodsData);
    }

    public function registerSupportChangeState()
    {
        if (
            ! empty($this->data['support_image'])
        ) {
            $listaPaymentReportsSupportMovement = [];
            collect($this->data['support_image'])->map(function ($lista) use (&$listaPaymentReportsSupportMovement) {

                $PaymentReportsSupportMovement = new PaymentReportsSupportMovement;

                $base64_str = substr($lista['base64'], strpos($lista['base64'], ',') + 1);

                $image = base64_decode($base64_str);

                $name = $this->PaymentReport->id.'/'.$this->PaymentReportsMovement->id.'/'.Str::random(40);

                $full_name = "{$name}.".$lista['extension'];
                Storage::disk('supports_change_estatus_report')->put($full_name, $image);

                $lista['soporte'] = "{$full_name}";
                $PaymentReportsSupportMovement->fill($lista);
                array_push($listaPaymentReportsSupportMovement, $PaymentReportsSupportMovement);
            });

            $this->PaymentReportsMovement->support_images()->saveMany($listaPaymentReportsSupportMovement);
        }
    }

    public function registerSupportPaymentMehods()
    {
        foreach ($this->PaymentReport->payment_reports_methods as $key => $payment_reports_methods) {
            if (
                isset($this->data['dataPayments'][$key]) &&
                ! empty($this->data['dataPayments'][$key]['support_image'])
            ) {
                $listasupportPaymentReportsMethod = [];
                collect($this->data['dataPayments'][$key]['support_image'])->map(function ($lista) use (&$listasupportPaymentReportsMethod, $payment_reports_methods) {

                    $supportPaymentReportsMethod = new supportPaymentReportsMethod;

                    $base64_str = substr($lista['base64'], strpos($lista['base64'], ',') + 1);

                    $image = base64_decode($base64_str);

                    $name = $this->PaymentReport->id.'/'.$payment_reports_methods->id.'/'.Str::random(40);

                    $full_name = "{$name}.".$lista['extension'];
                    Storage::disk('supports_prestamo')->put($full_name, $image);

                    $lista['support'] = "{$full_name}";
                    $supportPaymentReportsMethod->fill($lista);
                    array_push($listasupportPaymentReportsMethod, $supportPaymentReportsMethod);
                });

                $payment_reports_methods->support_images()->saveMany($listasupportPaymentReportsMethod);
            }
        }
    }

    // $cuotas recibe un array con los ids de las cuotas, prestamos_dia_id
    public function processPaymentCouotas($cuotas = [])
    {
        if (! empty($cuotas)) {
            // Extraer primero los IDs que YA estaban pagados (estos son los no procesados en este ciclo)
            $pagadas = PrestamosDias::whereIn('id', $cuotas)->where('pagado', true)->pluck('id')->toArray();
            if (! empty($pagadas)) {
                $this->prestamosIdNotProccess = array_merge($this->prestamosIdNotProccess, $pagadas);
            }

            // Luego actualizar únicamente las que sí estaban pendientes (pagado = false)
            PrestamosDias::whereIn('id', $cuotas)->where('pagado', false)->update(['pagado' => true]);
        }
    }

    public function processReportPaymentAproved()
    {
        if ($this->data['estatus_selected'] == 2) { // Si esta aprobado
            if ($this->PaymentReport->destination == 1) { // Pago de cutoas
                if ($this->PaymentReport->selected_payment_reports && count($this->PaymentReport->selected_payment_reports) > 0) {
                    $this->processPaymentCouotas($this->PaymentReport->selected_payment_reports->pluck('prestamos_dia_id')->toArray());

                    if (count($this->prestamosIdNotProccess) > 0) {
                        $idsReportados = $this->PaymentReport->selected_payment_reports->whereIn('prestamos_dia_id', $this->prestamosIdNotProccess)->pluck('id')->toArray();

                        // Legado: estaba como update(['error_process', true]) — columna sin valor.
                        SelectedPaymentReport::whereIn('id', $idsReportados)->update(['error_process' => true]);
                    }
                }
            }

            // Registramos saldo a favor
            if ($this->PaymentReport->importe > 0) {
                $data = $this->processBalance($this->PaymentReport);
                $this->processSummaryCustomer($data);
            }
        }
    }

    public function processBalance($PaymentReport, $refer_grupos_trabajos_user_id = null, $tipo_movimiento = 4)
    {

        $grupos_trabajos_user_id = $refer_grupos_trabajos_user_id;

        if (! $grupos_trabajos_user_id) {
            $grupos_trabajos_user_id = $this->grupos_trabajos_user_id;
        }

        $CustomerMovementHistory = CustomerMovementHistory::where('payment_report_id', $PaymentReport->id)->first();

        if ($CustomerMovementHistory) {
            throw new \Exception('No se puede procesar este informe de pago, ya se encuentra asignado.');
        }

        $CustomerMovementHistory = new CustomerMovementHistory;
        $CustomerMovementHistory->cliente_id = $PaymentReport->cliente_id;
        $CustomerMovementHistory->grupos_trabajos_user_id = $grupos_trabajos_user_id;
        $CustomerMovementHistory->date_movement = now();
        $CustomerMovementHistory->payment_report_id = $PaymentReport->id;
        $CustomerMovementHistory->amount = $PaymentReport->importe;
        $CustomerMovementHistory->type_movement_id = $tipo_movimiento;
        $CustomerMovementHistory->save();

        return $CustomerMovementHistory;
    }

    public function processSummaryCustomer($CustomerMovementHistory)
    {

        $TypesMovement = TypesMovement::find($CustomerMovementHistory->type_movement_id);

        $SummaryCustomerMovement = SummaryCustomerMovement::firstOrNew(['cliente_id' => $CustomerMovementHistory->cliente_id]);

        $SummaryCustomerMovement->cliente_id = $CustomerMovementHistory->cliente_id;

        if ($TypesMovement->operation == 'S') {
            $SummaryCustomerMovement->balance += $CustomerMovementHistory->amount;
            $SummaryCustomerMovement->credit += $CustomerMovementHistory->amount;
        } else {
            $SummaryCustomerMovement->balance = $SummaryCustomerMovement->balance - $CustomerMovementHistory->amount;
            $SummaryCustomerMovement->debit += $CustomerMovementHistory->amount;
        }

        $SummaryCustomerMovement->save();
    }

    public function registerPaymentCuotas()
    {
        if ($this->data['tipoPago'] == 1) { // cuotas
            if (isset($this->data['cuotas']) && ! empty($this->data['cuotas'])) {
                $cuotasData = array_map(function ($lista) {
                    $lista['prestamos_dia_id'] = $lista['id'];

                    return $lista;
                }, $this->data['cuotas']);

                $this->PaymentReport->selected_payment_reports()->createMany($cuotasData);
            }
        }
    }

    public function registerPositiveBalance()
    {
        if ($this->data['tipoPago'] == 1) { // cuotas
            $importePagos = array_sum(array_column($this->data['dataPayments'] ?? [], 'valor_importe'));
            $importeCuotas = array_sum(array_column($this->data['cuotas'] ?? [], 'cuota'));
            $importe = $importePagos - $importeCuotas;

            if ($importe > 0) {
                $this->PaymentReport->importe = $importe;
                $this->PaymentReport->save();
            }
        } else {
            $this->PaymentReport->importe = array_sum(array_column($this->data['dataPayments'] ?? [], 'valor_importe'));
            $this->PaymentReport->save();
        }
    }
}
