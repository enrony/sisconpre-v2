<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\CustomerMovementHistory;
use App\Models\PaymentReport;
use App\Models\PaymentReportsMovement;
use App\Models\PaymentReportsSupportMovement;
use App\Models\Prestamos;
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
    /** `types_movements` 01 "Pago" (resta del saldo a favor). */
    private const MOVIMIENTO_PAGO = 1;

    /** `payment_reports_movements_estatus` 2 "Aprobado". */
    private const ESTADO_APROBADO = 2;

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
        $this->PaymentReport = PaymentReport::with('payment_reports_movement_first.estatus_description')->findOrFail($this->data['id']);

        // 3. Obtenemos el movimiento y evitamos errores (Notice/Error) si resultase null
        $movement = $this->PaymentReport->payment_reports_movement_first;

        // El frontend ya deshabilita el cambio de estado cuando finish_estatus
        // es true (Aprobado/Rechazado/Remitido), pero eso no protege contra un
        // llamado directo al endpoint — se valida también acá.
        if ($movement && $movement->estatus_description?->finish_estatus) {
            throw new \Exception('Este informe está en un estado final y no se puede modificar.');
        }

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

    /**
     * Bloquea informar un pago sobre una cuota que ya está pagada o que ya
     * tiene otro informe de pago en curso (no finalizado) — el frontend ya
     * las oculta/deshabilita, pero esto es lo que realmente lo impide: sin
     * esto, dos pestañas o una llamada directa a la API podían duplicar el
     * informe sobre la misma cuota.
     */
    public function verifiedCuotasDisponibles(): void
    {
        if (($this->data['tipoPago'] ?? null) != 1) { // Solo aplica a pago de cuotas
            return;
        }

        $cuotaIds = array_column($this->data['cuotas'] ?? [], 'id');

        if (empty($cuotaIds)) {
            return;
        }

        // Las cuotas tienen que ser de préstamos del cliente del informe (el
        // saldo a favor y la cartera son por cliente).
        $ajenas = PrestamosDias::whereIn('id', $cuotaIds)
            ->whereHas('Prestamo', fn ($q) => $q->where('cliente_id', '!=', (int) ($this->data['cliente']['id'] ?? 0)))
            ->pluck('id');

        if ($ajenas->isNotEmpty()) {
            throw new \Exception('La(s) cuota(s) #'.$ajenas->implode(', #').' no pertenece(n) a un préstamo de este cliente.');
        }

        $noDisponibles = PrestamosDias::with('pendientesPago.paymentReport.payment_reports_movement_first.estatus_description')
            ->whereIn('id', $cuotaIds)
            ->get()
            ->filter(function (PrestamosDias $cuota) {
                if ($cuota->pagado) {
                    return true;
                }

                $estatusReserva = $cuota->pendientesPago?->paymentReport?->payment_reports_movement_first?->estatus_description;

                return $estatusReserva && ! $estatusReserva->finish_estatus;
            })
            ->pluck('id');

        if ($noDisponibles->isNotEmpty()) {
            throw new \Exception('La(s) cuota(s) #'.$noDisponibles->implode(', #').' ya no está(n) disponible(s) para informar un pago (ya fue(ron) pagada(s) o tiene(n) un informe de pago en curso).');
        }
    }

    /**
     * Bloquea informar un pago de cuotas contra un préstamo que no esté
     * aprobado (pendiente de aprobación o rechazado). `scopePrestamoActivo()`
     * ya lo oculta del buscador de "Informar un pago", pero eso no protege
     * contra un llamado directo al endpoint. No aplica a saldo a favor (sin
     * cuotas, sin préstamo referenciado).
     */
    public function verifiedPrestamosAprobados(): void
    {
        if (($this->data['tipoPago'] ?? null) != 1) {
            return;
        }

        $cuotaIds = array_column($this->data['cuotas'] ?? [], 'id');

        if (empty($cuotaIds)) {
            return;
        }

        $prestamoIds = PrestamosDias::whereIn('id', $cuotaIds)->pluck('prestamo_id')->unique();

        $noAprobados = Prestamos::whereIn('id', $prestamoIds)
            ->where('aprobacion_estatus_id', '!=', Prestamos::APROBACION_APROBADO)
            ->pluck('id');

        if ($noAprobados->isNotEmpty()) {
            throw new \Exception('El préstamo #'.$noAprobados->implode(', #').' todavía no está aprobado — no se le puede informar un pago.');
        }
    }

    /**
     * Dos modalidades de pago de cuotas, sin mezcla:
     * - Con dinero: lo informado no puede ser menor que las cuotas elegidas;
     *   el excedente queda como saldo a favor.
     * - Con saldo a favor (`pagoConSaldo`): sin métodos de pago; las cuotas no
     *   pueden superar el saldo disponible. Se aprueba en el acto
     *   (`aprobarPagoConSaldo()`), porque ese dinero ya se verificó al entrar.
     *
     * Los montos de las cuotas se toman de la BD, no de lo que manda el front.
     */
    public function verifiedImportes(): void
    {
        $this->data['saldo_favor_aplicado'] = 0;
        $pagoConSaldo = (bool) ($this->data['pagoConSaldo'] ?? false);

        if (($this->data['tipoPago'] ?? null) != 1) {
            if ($pagoConSaldo) {
                throw new \Exception('El saldo a favor solo se puede usar para pagar cuotas.');
            }

            return;
        }

        $montos = PrestamosDias::whereIn('id', array_column($this->data['cuotas'] ?? [], 'id'))->pluck('cuota', 'id');
        $this->data['cuotas'] = array_map(
            fn (array $c): array => ['id' => $c['id'], 'cuota' => (float) ($montos[$c['id']] ?? 0)],
            $this->data['cuotas'] ?? [],
        );
        $totalCuotas = round(array_sum(array_column($this->data['cuotas'], 'cuota')), 3);

        if ($pagoConSaldo) {
            if (! empty($this->data['dataPayments'])) {
                throw new \Exception('El pago con saldo a favor no lleva métodos de pago.');
            }

            $disponible = (new SaldoFavorService)->resumen((int) ($this->data['cliente']['id'] ?? 0))['disponible'];

            if ($totalCuotas > $disponible + 0.001) {
                throw new \Exception('Las cuotas elegidas suman '.$this->formato($totalCuotas).' y el saldo a favor disponible es '.$this->formato($disponible).'.');
            }

            $this->data['saldo_favor_aplicado'] = $totalCuotas;

            return;
        }

        $totalPagos = round(array_sum(array_column($this->data['dataPayments'] ?? [], 'valor_importe')), 3);

        if ($totalPagos + 0.001 < $totalCuotas) {
            throw new \Exception('El pago informado ('.$this->formato($totalPagos).') no cubre las cuotas elegidas ('.$this->formato($totalCuotas).'): faltan '.$this->formato($totalCuotas - $totalPagos).'.');
        }
    }

    public function esPagoConSaldo(): bool
    {
        return (float) ($this->data['saldo_favor_aplicado'] ?? 0) > 0;
    }

    /** Un pago con saldo a favor nace aprobado; uno con dinero, pendiente de gestión. */
    public function registerMovimientoInicial(): void
    {
        if (! $this->esPagoConSaldo()) {
            $this->registerPaymentMovement();

            return;
        }

        $this->registerPaymentMovement(self::ESTADO_APROBADO, 'Pagado con saldo a favor (aprobación automática)');
        $this->PaymentReport->payment_reports_movements_estatus_id = self::ESTADO_APROBADO;
        $this->PaymentReport->save();
    }

    /** Marca las cuotas pagadas, descuenta el saldo y cierra el préstamo si quedó saldado. */
    public function aprobarPagoConSaldo(): void
    {
        if (! $this->esPagoConSaldo()) {
            return;
        }

        $this->data['estatus_selected'] = self::ESTADO_APROBADO;
        $this->processReportPaymentAproved();
    }

    private function formato(float $monto): string
    {
        return number_format($monto, 0, ',', '.');
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
            $prestamoIds = $this->PaymentReport->selected_payment_reports()
                ->join('prestamos_dias', 'prestamos_dias.id', '=', 'selected_payment_reports.prestamos_dia_id')
                ->pluck('prestamos_dias.prestamo_id')
                ->unique();

            $this->verifiedPrestamosEnCurso($prestamoIds->all());

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

            // Descontamos el saldo a favor que se aplicó a las cuotas
            $this->processSaldoFavorAplicado();

            // Registramos saldo a favor
            if ($this->PaymentReport->importe > 0) {
                $data = $this->processBalance($this->PaymentReport);
                $this->processSummaryCustomer($data);
            }

            // El préstamo que quedó con todas sus cuotas pagadas pasa a "Pagado"
            $estados = new PrestamoEstadoService;
            foreach ($prestamoIds as $prestamoId) {
                $estados->cerrarSiSaldado((int) $prestamoId);
            }
        }
    }

    /**
     * No se aprueba un pago sobre un préstamo que, desde que se informó, dejó
     * de estar en curso (anulado / perdido / pagado) o perdió la aprobación.
     *
     * @param  array<int, mixed>  $prestamoIds
     */
    private function verifiedPrestamosEnCurso(array $prestamoIds): void
    {
        $noOperativos = Prestamos::whereIn('id', $prestamoIds)
            ->where(fn ($q) => $q->where('estatus', '!=', Prestamos::ESTATUS_PENDIENTE)
                ->orWhere('aprobacion_estatus_id', '!=', Prestamos::APROBACION_APROBADO))
            ->pluck('id');

        if ($noOperativos->isNotEmpty()) {
            throw new \Exception('No se puede aprobar: el préstamo #'.$noOperativos->implode(', #').' ya no está en curso o no está aprobado.');
        }
    }

    private function processSaldoFavorAplicado(): void
    {
        $saldo = (float) $this->PaymentReport->saldo_favor_aplicado;

        if ($saldo <= 0) {
            return;
        }

        $balance = (float) SummaryCustomerMovement::where('cliente_id', $this->PaymentReport->cliente_id)->value('balance');

        if ($saldo > $balance + 0.001) {
            throw new \Exception('El cliente ya no tiene saldo a favor suficiente para cubrir '.number_format($saldo, 0, ',', '.').'.');
        }

        $movimiento = $this->processBalance($this->PaymentReport, null, self::MOVIMIENTO_PAGO, $saldo);
        $this->processSummaryCustomer($movimiento);
    }

    public function processBalance($PaymentReport, $refer_grupos_trabajos_user_id = null, $tipo_movimiento = 4, ?float $monto = null)
    {

        $grupos_trabajos_user_id = $refer_grupos_trabajos_user_id;

        if (! $grupos_trabajos_user_id) {
            $grupos_trabajos_user_id = $this->grupos_trabajos_user_id;
        }

        // Un mismo informe puede generar un débito (saldo aplicado) y un
        // crédito (excedente): lo que no puede repetirse es el mismo tipo.
        $CustomerMovementHistory = CustomerMovementHistory::where('payment_report_id', $PaymentReport->id)
            ->where('type_movement_id', $tipo_movimiento)
            ->first();

        if ($CustomerMovementHistory) {
            throw new \Exception('No se puede procesar este informe de pago, ya se encuentra asignado.');
        }

        $CustomerMovementHistory = new CustomerMovementHistory;
        $CustomerMovementHistory->cliente_id = $PaymentReport->cliente_id;
        $CustomerMovementHistory->grupos_trabajos_user_id = $grupos_trabajos_user_id;
        $CustomerMovementHistory->date_movement = now();
        $CustomerMovementHistory->payment_report_id = $PaymentReport->id;
        $CustomerMovementHistory->amount = $monto ?? $PaymentReport->importe;
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
            $importe = $importePagos + (float) ($this->data['saldo_favor_aplicado'] ?? 0) - $importeCuotas;

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
