<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    /**
     * @return BelongsTo<PrestamosDias, $this>
     */
    public function PrestamosDias(): BelongsTo
    {
        return $this->belongsTo(PrestamosDias::class, 'prestamos_dia_id');
    }

    /**
     * @return BelongsTo<PaymentReport, $this>
     */
    public function paymentReport(): BelongsTo
    {
        return $this->belongsTo(PaymentReport::class, 'payment_report_id');
    }
}
