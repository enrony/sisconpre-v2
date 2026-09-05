<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReport extends Model
{
    use HasFactory;

    protected $array_estatus = ['', 'Pendiente', 'Aprobado', 'Rechazado', 'En revisión', 'Remitido'];

    protected $array_estatus_fcolor = ['', 'text-gray-400', 'text-green-400', 'text-red-400', 'text-yellow-400', 'text-yellow-800'];

    protected $fillable = [
        'cliente_id',
        'cliente',
        'destination',
        'type_payment_record_id',
        'grupos_trabajos_user_id',
        'approved',
        'payment_report_id',
        'motivo',
        'estatus',
        'importe',
        'payment_reports_movements_estatus_id',
    ];

    protected $appends = [
        'value_amount',
        'destination_text',
        'number_cuotas',
        'status_description',
    ];

    public function payment_reports_movements_estatus()
    {
        return $this->belongsTo(PaymentReportsMovementsEstatu::class, 'payment_reports_movements_estatus_id');
    }

    public function getNumberCuotasAttribute()
    {
        return count($this->selected_payment_reports);
    }

    public function getStatusDescriptionAttribute()
    {
        if (count($this->payment_reports_movement) > 0) {
            $result = $this->payment_reports_movement[0]->estatus_description;
            if ($result) {
                return $this->objectStatusPayment($result);
            }
        }

        return $this->objectStatusPayment(1);
    }

    public function objectStatusPayment($result)
    {
        return (object) [
            'id' => $result->id,
            'desc' => $result->description,
            'color' => $result->style,
            'finish_estatus' => (bool) $result->finish_estatus,
        ];
    }

    public function getDestinationTextAttribute()
    {
        return $this->destination == 1 ? 'Pago de cuota(s)' : 'Saldo a favor';
    }

    public function getValueAmountAttribute()
    {

        if (count($this->payment_reports_methods) > 0) {
            return $this->payment_reports_methods->sum(function ($payment_reports_method) {
                return $payment_reports_method->importe;
            });
        }

        return 0;
    }

    public function getClienteAttribute($value)
    {
        return json_decode($value);
    }

    public function setClienteAttribute($value)
    {
        $this->attributes['cliente'] = json_encode($value);
        $this->attributes['cliente_id'] = $value['id'];
    }

    public function payment_reports_methods()
    { // usar solo para registro, no agregar order ni otra condicion, ya que puede afectar el correcto funcionamiento al registrar
        // desde informe de pagos
        return $this->hasMany(paymentReportsMethod::class, 'payment_report_id');
    }

    public function selected_payment_reports()
    {
        return $this->hasMany(SelectedPaymentReport::class, 'payment_report_id');
    }

    public function payment_reports_movement()
    { // No quitar el latest
        return $this->hasMany(PaymentReportsMovement::class, 'payment_report_id')->latest();
    }

    public function payment_reports_movement_first()
    { // No quitar el latest
        return $this->hasOne(PaymentReportsMovement::class, 'payment_report_id')
            ->latest();
        // ->limit(1)
        // ->with(['estatus_description_await'])
        // ->whereHas('estatus_description_await')
    }

    public function payment_reports_movement_first_bad()
    {
        $estadosFiltrar = request()->filled('estados') ? explode(',', request()->input('estados')) : [];

        return $this->hasOne(PaymentReportsMovement::class, 'payment_report_id')
            ->latest()
            ->when(! empty($estadosFiltrar), function ($query) use ($estadosFiltrar) {
                $query->whereIn('estatus', $estadosFiltrar);
            });
    }

    public function scopeMovimientos()
    { // No quitar el latest

        $res = $this->hasMany(PaymentReportsMovement::class, 'payment_report_id')->latest()->first();

        if ($res) {
            return $res->whereIn('estatus', [1, 4]);
        }

        return null;

        // return $this->hasMany( PaymentReportsMovement::class, 'payment_report_id')->latest()->first()->whereIn('estatus', [1,4]);
    }

    public function scopePaymentReportActivo($query, $cliente)
    {
        return $query->where('cliente_id', $cliente)->where('approved', false)->where('estatus', 1)
            // ->Movimientos()
            ->whereHas('payment_reports_movement', function ($query) {
                // $query->latest();
                // ->whereIn('estatus', [1,4]);//Las que esten pendientes o en revisión
                // Subconsulta para obtener el último registro relacionado
                // $movement->whereRaw("id = {$lastId->id}");
                // Aplicar la condición al último registro
                // $query->whereIn('estatus', [1,4]);
            });
    }

    // public static function lista($request)
    // {
    //     $table = "payment_reports";
    //     $arrayIDPrestamos = [];
    //     $estadosFiltrar = $request->filled('estados') ? explode(',', $request->input('estados')) : null;

    //     $query1 = (new static)::selectRaw("{$table}.*, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
    //         ->when($request->term, function ($query, $term) use ($table) {
    //             $query->where("{$table}.description", 'LIKE', '%' . $term . '%');
    //         })->where("{$table}.estatus", 1)
    //         ->with(['payment_reports_movement_first' => function ($query) use ($estadosFiltrar) {
    //             $query->latest(); // Primero ordenar por el más reciente
    //             $query->take(1); // Luego tomar solo el primero (el más reciente)
    //             if ($estadosFiltrar) {
    //                 $query->whereIn('estatus', $estadosFiltrar); // Filtrar los resultados *después* de tomar el último
    //             }
    //         }])
    //         ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($query) use ($request, $table) {
    //             $fechaInicio = Carbon::parse($request->fecha_registro1)->startOfDay();
    //             $fechaFin = Carbon::parse($request->fecha_registro2)->endOfDay();
    //             $query->whereBetween("{$table}.created_at", [$fechaInicio, $fechaFin]);
    //         })
    //         ->latest("{$table}.created_at")->get()->map(function ($item) use ($arrayIDPrestamos, $estadosFiltrar) {
    //             // $item->payment_reports_movement_first ahora debería ser el último movimiento,
    //             // y será null si no existe ningún movimiento que cumpla con el filtro (si se aplicó)
    //             if (!($estadosFiltrar && $item->payment_reports_movement_first === null)) {
    //                 // Si se aplicó el filtro de estados y no se encontró ningún movimiento,
    //                 // puedes optar por filtrar el payment_report completo aquí si es necesario.
    //                 //return null; // O realiza alguna otra lógica para excluir este payment_report

    //                 $prestamoFormated = [];

    //                 if (count($item->selected_payment_reports) > 0) {
    //                     foreach ($item->selected_payment_reports as $selected_payment_report) {

    //                         $idPrestamo = $selected_payment_report->PrestamosDias->prestamo_id;

    //                         if (!in_array($idPrestamo, $arrayIDPrestamos)) {

    //                             array_push($arrayIDPrestamos, $idPrestamo);

    //                             $valor = json_decode(json_encode($selected_payment_report->PrestamosDias->Prestamo));
    //                             $position = count($prestamoFormated);

    //                             $prestamoFormated[$position] = $valor;
    //                             $prestamoFormated[$position]->prestamosss_dias = [];
    //                         }

    //                         $nuevo = $selected_payment_report->PrestamosDias;

    //                         $prestamoFormated[$position]->prestamosss_dias[] = $nuevo;
    //                     }
    //                 }

    //                 $item->prestamoFormated = $prestamoFormated;

    //                 return $item;
    //             }
    //         });

    //     $queryAll = collect($query1);

    //     return $queryAll;
    // }
    public static function lista($request)
    {
        $table = 'payment_reports';

        $arrayIDPrestamos = [];

        $query1 = (new static)::selectRaw("{$table}.*, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
            ->when($request->term, function ($query, $term) use ($table) {
                $query->where("{$table}.description", 'LIKE', '%'.$term.'%');
            })->where("{$table}.estatus", 1)
            ->with(['payment_reports_movement_first'])

            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($query) use ($request, $table) {
                $fechaInicio = Carbon::parse($request->fecha_registro1)->startOfDay();
                $fechaFin = Carbon::parse($request->fecha_registro2)->endOfDay();
                $query->whereBetween("{$table}.created_at", [$fechaInicio, $fechaFin]);
            })
            ->when($request->filled(['clientes']), function ($query) use ($request, $table) {
                $clientes = explode(',', $request->clientes);
                if (count($clientes) > 0) {
                    $query->whereIn("{$table}.cliente_id", $clientes);
                }
            })
            ->when($request->filled(['estados']), function ($query) use ($request, $table) {
                $estados = explode(',', $request->estados);
                if (count($estados) > 0) {
                    $query->whereIn("{$table}.payment_reports_movements_estatus_id", $estados);
                }
            })
            ->when($request->filled(['destinoPago']), function ($query) use ($request, $table) {
                $destinoPago = explode(',', $request->destinoPago);
                if (count($destinoPago) > 0) {
                    $query->whereIn("{$table}.destination", $destinoPago);
                }
            })
            ->latest("{$table}.created_at")->get()->map(function ($item) use ($arrayIDPrestamos) {

                $prestamoFormated = [];

                if (count($item->selected_payment_reports) > 0) {
                    foreach ($item->selected_payment_reports as $selected_payment_report) {

                        $idPrestamo = $selected_payment_report->PrestamosDias->prestamo_id;

                        if (! in_array($idPrestamo, $arrayIDPrestamos)) {

                            array_push($arrayIDPrestamos, $idPrestamo);

                            $valor = json_decode(json_encode($selected_payment_report->PrestamosDias->Prestamo));
                            $position = count($prestamoFormated);

                            $prestamoFormated[$position] = $valor;
                            $prestamoFormated[$position]->prestamosss_dias = [];
                        }

                        $nuevo = $selected_payment_report->PrestamosDias;

                        $prestamoFormated[$position]->prestamosss_dias[] = $nuevo;
                    }
                }

                $item->prestamoFormated = $prestamoFormated;

                return $item;
            });

        // if($request->filled('estados')){
        //     $query1 = $query1->filter(function ($item) use ($request) {
        //         return (
        //             $item->payment_reports_movement_first &&
        //             in_array($item->payment_reports_movement_first->estatus, explode(',', $request->input('estados'))));
        //     });

        // }

        $queryAll = collect($query1);

        return $queryAll;
    }
}
