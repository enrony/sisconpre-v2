<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReportsMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_report_id',
        'estatus',
        'grupos_trabajos_user_id',
        'motivo',
    ];

    public function estatus_description()
    {
        return $this->belongsTo(PaymentReportsMovementsEstatu::class, 'estatus');
    }

    public function estatus_description_await()
    {
        return $this->belongsTo(PaymentReportsMovementsEstatu::class, 'estatus');
        // ->where('finish_estatus', false)
    }

    public function support_images() // usar solo para registro, no agregar order ni otra condicion, ya que puede afectar el correcto funcionamiento al registrar
    {// desde informe de pagos
        return $this->hasMany(PaymentReportsSupportMovement::class, 'payment_reports_movement_id');
    }
}
