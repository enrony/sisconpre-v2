<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestamosDias extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestamo_id',
        'grupos_trabajos_user_id',
        'cuota',
        'pagado',
        'demorado',
        'fecha_pago',
        'estatus',

        'date',
        'apply',
        'dom',
        'festivo',
        'sigla',
        'date_before',
        'date_change',

        'prestamo_dia_id',
        'is_surcharge',
        'apply_surcharge',
        'surcharge',
        'days_apply_surcharge',
        'day_apply_surcharge',
        'surcharge_applied',
        'notified_surcharge',
        'date_surcharge_notified',
        'error_sending_notification',
    ];

    protected $casts = [
        'apply' => 'boolean',
        'apply_surcharge' => 'boolean',
        'surcharge_applied' => 'boolean',
        'is_surcharge' => 'boolean',
        'date_change' => 'boolean',
        'notified_surcharge' => 'boolean',
        'dom' => 'boolean',
        'festivo' => 'boolean',
        'pagado' => 'boolean',
        'demorado' => 'boolean',
        'day_apply_surcharge' => 'date',
    ];

    public function Prestamo()
    {
        return $this->belongsTo(Prestamos::class, 'prestamo_id');
    }

    // Pendientes por selección en informe de pago
    public function pendientesPago()
    {
        return $this->hasOne(SelectedPaymentReport::class, 'prestamos_dia_id')->with(['paymentReport' => function ($paymentReport) {
            $paymentReport->with(['payment_reports_movement_first' => function ($payment_reports_movement_first) {
                $payment_reports_movement_first->with(['estatus_description']);
            }]);
        }]);

    }

    // public function set

}
