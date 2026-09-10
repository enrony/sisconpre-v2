<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * Atributos calculados por el builder `lista()` (no son columnas):
 *
 * @property-read string|null $created
 * @property-read string|null $cliente_nombre
 * @property-read string|null $cliente_apellido
 * @property-read string|null $cliente_documento
 */
class Prestamos extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'grupos_trabajos_user_id',
        'monto_prestamo',
        'cuota',
        'pagado',
        'anulado',
        'perdido',
        'estatus',
        'cliente',
        'tasa',
        'form',
        'incluir_festivos',
        'incluir_domingos',
        'cuota_sugerida',
        'valor_cuota_sugerida',
        'cuota_establecida',
        'date_first_pay',
        'date_last_pay',
        'country_id',
        'utilidad',
        'tipo_prestamo_id',
        'total',
        'apply_surcharge',
        'incluir_festivos_surcharge',
        'incluir_domingos_surcharge',
        'pause_surcharge',
    ];

    protected $hidden = ['form'];

    protected $casts = [
        'incluir_festivos' => 'boolean',
        'incluir_domingos' => 'boolean',
        'incluir_domingos_surcharge' => 'boolean',
        'incluir_festivos_surcharge' => 'boolean',
        'perdido' => 'boolean',
        'anulado' => 'boolean',
        'pagado' => 'boolean',
        'pause_surcharge' => 'boolean',
        'cuota_sugerida' => 'boolean',
        'apply_surcharge' => 'boolean',
        'date_first_pay' => 'date',
        'date_last_pay' => 'date',
    ];

    // Portado de L8: eran `static function` con `Self::belongsTo(...)` (roto en PHP 8.3).
    // Se corrige a método de instancia y se arregla el orden de argumentos de belongsTo.
    public function datoCliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function scopePrestamoActivo($query, $cliente)
    {
        return $query->where('cliente_id', $cliente)->where('pagado', false)->where('anulado', false)->where('perdido', false)->where('estatus', 1)->whereHas('prestamos_dias');
    }

    public function scopeCliente($query, $id)
    {
        return $query->with(['datoCliente'])->get();
    }

    /**
     * @return HasMany<PrestamosDias, $this>
     */
    public function prestamos_dias(): HasMany
    {
        return $this->hasMany(PrestamosDias::class, 'prestamo_id');
    }

    public function getDateFirstPayAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getDateLastPayAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function setClienteAttribute($value)
    {
        $this->attributes['cliente'] = (is_null($value) ? null : json_encode($value));
    }

    public function setFormAttribute($value)
    {
        $this->attributes['form'] = (is_null($value) ? null : json_encode($value));
    }

    public function getClienteAttribute()
    {
        return is_null($this->attributes['cliente']) ? null : json_decode($this->attributes['cliente']);
    }

    /**
     * @return BelongsTo<PrestamosEstatu, $this>
     */
    public function p_estatus(): BelongsTo
    {
        return $this->belongsTo(PrestamosEstatu::class, 'estatus');
    }

    /** Columnas por las que la grilla puede ordenar → columna real (evita inyección en `orderBy`). */
    private const ORDENABLES = [
        'id' => 'prestamos.id',
        'created' => 'prestamos.created_at',
        'date_first_pay' => 'prestamos.date_first_pay',
        'date_last_pay' => 'prestamos.date_last_pay',
        'monto_prestamo' => 'prestamos.monto_prestamo',
        'tasa' => 'prestamos.tasa',
        'utilidad' => 'prestamos.utilidad',
        'total' => 'prestamos.total',
    ];

    /**
     * Builder del listado de préstamos (grilla de la consola), listo para paginar.
     * Devuelve SOLO lo que consume `PrestamosTable.vue` + nombre/apellido/documento
     * del cliente vía `json_extract` (sin el snapshot anidado, que en filas legadas
     * llega a 2,8 MB). `prestamos_dias` (detalle expandible) y `p_estatus` van
     * eager-loaded — ya sólo para la página vigente, no para toda la tabla.
     *
     * El segundo parámetro se mantiene por compatibilidad con la llamada existente.
     *
     * @return Builder<static>
     */
    public static function lista(Request $request, ?self $prestamos = null)
    {
        $csvIds = static fn (mixed $csv): array => array_values(array_filter(
            explode(',', (string) $csv),
            static fn (string $v): bool => $v !== '',
        ));

        $sortCol = $request->query('sortColumn');
        $sortBy = is_string($sortCol) ? (self::ORDENABLES[$sortCol] ?? 'prestamos.id') : 'prestamos.id';

        $sortDir = $request->query('sortOrder');
        $order = is_string($sortDir) && strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return static::query()
            ->select([
                'prestamos.id',
                'prestamos.cliente_id',
                'prestamos.monto_prestamo',
                'prestamos.tasa',
                'prestamos.utilidad',
                'prestamos.total',
                'prestamos.estatus',
                'prestamos.date_first_pay',
                'prestamos.date_last_pay',
                'prestamos.pause_surcharge',
                'prestamos.created_at',
            ])
            ->selectRaw("date_format(prestamos.created_at, '%Y-%m-%d %H:%i:%s') as created")
            ->selectRaw("json_unquote(json_extract(prestamos.cliente, '$.nombre')) as cliente_nombre")
            ->selectRaw("json_unquote(json_extract(prestamos.cliente, '$.apellido')) as cliente_apellido")
            ->selectRaw("json_unquote(json_extract(prestamos.cliente, '$.documento')) as cliente_documento")
            ->with([
                'prestamos_dias:id,prestamo_id,cuota,pagado,date,apply,dom,festivo,sigla',
                'p_estatus:id,type_tag',
            ])
            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($query) use ($request) {
                $query->whereBetween('prestamos.created_at', [
                    Carbon::parse($request->fecha_registro1)->startOfDay(),
                    Carbon::parse($request->fecha_registro2)->endOfDay(),
                ]);
            })
            ->when($request->filled(['inicio1', 'inicio2']), function ($query) use ($request) {
                $query->whereBetween('prestamos.date_first_pay', [$request->inicio1, $request->inicio2]);
            })
            ->when($request->filled(['fin1', 'fin2']), function ($query) use ($request) {
                $query->whereBetween('prestamos.date_last_pay', [$request->fin1, $request->fin2]);
            })
            ->when($request->filled('clientes'), function ($query) use ($request, $csvIds) {
                $query->whereIn('prestamos.cliente_id', $csvIds($request->clientes));
            })
            ->when($request->filled('estados'), function ($query) use ($request, $csvIds) {
                $query->whereIn('prestamos.estatus', $csvIds($request->estados));
            })
            ->orderBy($sortBy, $order);
    }
}
