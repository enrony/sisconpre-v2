<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Flags de decoración que algunos controladores adosan en runtime para el front
 * (no son columnas): `PaymentReportController::record()` marca la cuota
 * seleccionada en un informe de pago.
 *
 * @property bool $p_seleccionado
 * @property string $textColorCuotas
 */
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

    /**
     * @return BelongsTo<Prestamos, $this>
     */
    public function Prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamos::class, 'prestamo_id');
    }

    /**
     * Última vez que esta cuota fue incluida en un informe de pago (si una
     * cuota se informó, se rechazó, y se volvió a informar, esta relación
     * debe reflejar la más reciente — `latest()` es necesario para eso, no
     * cosmético: sin él, "¿está reservada?" podría evaluarse contra un
     * informe viejo ya resuelto).
     *
     * @return HasOne<SelectedPaymentReport, $this>
     */
    public function pendientesPago(): HasOne
    {
        return $this->hasOne(SelectedPaymentReport::class, 'prestamos_dia_id')
            ->latest()
            ->with(['paymentReport' => function ($paymentReport) {
                $paymentReport->with(['payment_reports_movement_first' => function ($payment_reports_movement_first) {
                    $payment_reports_movement_first->with(['estatus_description']);
                }]);
            }]);
    }

    // public function set

}
