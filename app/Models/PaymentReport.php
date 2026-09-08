<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

/**
 * Atributos calculados por el builder `lista()` (no son columnas de la tabla):
 *
 * @property-read string|null $cliente_nombre
 * @property-read string|null $cliente_apellido
 * @property-read string|null $cliente_documento
 * @property-read string|null $value_amount_sum
 * @property-read int $selected_payment_reports_count
 */
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

    /**
     * @return HasOne<PaymentReportsMovement, $this>
     */
    public function payment_reports_movement_first(): HasOne
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

    /**
     * Builder del listado de informes de pago (consola de gestión), listo para
     * paginar. Devuelve SOLO lo que consume `PaymentReportTable.vue`: id, cliente
     * (nombre/apellido/documento extraídos del JSON, sin arrastrar el snapshot
     * anidado del cliente), destino, monto (Σ de métodos), nº de cuotas y el
     * último estado. El árbol de préstamos/cuotas se pide aparte por `record($id)`.
     *
     * El segundo parámetro se mantiene por compatibilidad con las llamadas
     * existentes (`PaymentReport::lista($request, $PaymentReport)`).
     *
     * @return Builder<static>
     */
    public static function lista(Request $request, ?self $PaymentReport = null)
    {
        $table = 'payment_reports';

        $csvIds = static fn (mixed $csv): array => array_values(array_filter(
            explode(',', (string) $csv),
            static fn (string $v): bool => $v !== '',
        ));

        return static::query()
            ->select([
                "{$table}.id",
                "{$table}.cliente_id",
                "{$table}.destination",
                "{$table}.approved",
                "{$table}.payment_report_id",
                "{$table}.motivo",
                "{$table}.importe",
                "{$table}.estatus",
                "{$table}.payment_reports_movements_estatus_id",
                "{$table}.created_at",
                "{$table}.updated_at",
            ])
            ->selectRaw("json_unquote(json_extract({$table}.cliente, '$.nombre')) as cliente_nombre")
            ->selectRaw("json_unquote(json_extract({$table}.cliente, '$.apellido')) as cliente_apellido")
            ->selectRaw("json_unquote(json_extract({$table}.cliente, '$.documento')) as cliente_documento")
            ->where("{$table}.estatus", 1)
            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($query) use ($request, $table) {
                $query->whereBetween("{$table}.created_at", [
                    Carbon::parse($request->fecha_registro1)->startOfDay(),
                    Carbon::parse($request->fecha_registro2)->endOfDay(),
                ]);
            })
            ->when($request->filled('clientes'), function ($query) use ($request, $table, $csvIds) {
                $query->whereIn("{$table}.cliente_id", $csvIds($request->clientes));
            })
            ->when($request->filled('estados'), function ($query) use ($request, $table, $csvIds) {
                $query->whereIn("{$table}.payment_reports_movements_estatus_id", $csvIds($request->estados));
            })
            ->when($request->filled('destinoPago'), function ($query) use ($request, $table, $csvIds) {
                $query->whereIn("{$table}.destination", $csvIds($request->destinoPago));
            })
            ->withCount('selected_payment_reports')
            ->withSum('payment_reports_methods as value_amount_sum', 'importe')
            ->with('payment_reports_movement_first.estatus_description')
            ->latest("{$table}.created_at");
    }
}
