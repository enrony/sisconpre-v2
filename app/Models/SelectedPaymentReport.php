<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectedPaymentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_report_id',
        'prestamos_dia_id',
        'estatus',
        'error_process',
    ];

    protected $casts = [
        'error_process' => 'boolean',
    ];

    // public function Prestamos(){
    //     return $this->belongsToMany(PrestamosDias::class, 'payment_reports', 'payment_report_id', 'prestamos_dia_isd');
    // }
    public function PrestamosDias()
    {
        return $this->belongsTo(PrestamosDias::class, 'prestamos_dia_id');
    }

    public function paymentReport()
    {
        return $this->belongsTo(PaymentReport::class, 'payment_report_id');
    }
}
