<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class paymentReportsMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_report_id',
        'payment_method_id',
        'bank_id',
        'franquicia_id',
        'referencia',
        'importe',
        'estatus',
        'motivo',
        'estatus',
    ];

    public function support_images() // usar solo para registro, no agregar order ni otra condicion, ya que puede afectar el correcto funcionamiento al registrar
    {// desde informe de pagos
        return $this->hasMany(supportPaymentReportsMethod::class, 'payment_reports_method_id');
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * @return BelongsTo<Bank, $this>
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
