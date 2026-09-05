<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function prestamos_dias()
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

    public function p_estatus()
    {
        return $this->belongsTo(PrestamosEstatu::class, 'estatus');
    }

    public static function lista($request)
    {

        $sortBy = $request->query('sortColumn', 'created'); // Columna
        $order = $request->query('sortOrder', 'desc'); // Orden

        return self::with(['prestamos_dias', 'p_estatus' => function ($p) {
            $p->select('id', 'type_tag');
        }])
            ->select('*', 'created_at as created')
            ->when($request->filled(['fecha_registro1', 'fecha_registro2']), function ($query) use ($request) {
                $fechaInicio = Carbon::parse($request->fecha_registro1)->startOfDay();
                $fechaFin = Carbon::parse($request->fecha_registro2)->endOfDay();

                $query->whereBetween('prestamos.created_at', [$fechaInicio, $fechaFin]);
            })
            ->when($request->filled(['inicio1', 'inicio2']), function ($query) use ($request) {
                $query->whereBetween('prestamos.date_first_pay', [$request->inicio1, $request->inicio1]);
            })
            ->when($request->filled(['fin1', 'fin2']), function ($query) use ($request) {
                $query->whereBetween('prestamos.date_last_pay', [$request->fin1, $request->fin1]);
            })
            ->when($request->filled(['clientes']), function ($query) use ($request) {
                $clientes = explode(',', $request->clientes);
                if (count($clientes) > 0) {
                    $query->whereIn('prestamos.cliente_id', $clientes);
                }
            })
            ->when($request->filled(['estados']), function ($query) use ($request) {
                $estados = explode(',', $request->estados);
                if (count($estados) > 0) {
                    $query->whereIn('prestamos.estatus', $estados);
                }
            })
            ->orderBy($sortBy, $order)
            ->get();
    }
}
