<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;

    public $fillable = [
        'documento',
        'nombre',
        'nombre_segundo',
        'apellido',
        'apellido_segundo',
        'telefono',
        'direccion',
        'grupos_trabajos_user_id',
        'idtipo_documento',
        'email',
        'city_id',
        'email_verified_at',
    ];

    protected $with = ['tipo_documento'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'full_document',
        'prestamos_activos',
        'country_id',
        'informes_pendientes',
    ];

    public function tipo_documento()
    {
        return $this->belongsTo(tiposDocumentos::class, 'idtipo_documento');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function Pretamos()
    {
        return $this->hasMany(Prestamos::class, 'cliente_id')->whereHas('prestamos_dias');
    }

    public function InformesPandientesActivos()
    {
        return $this->hasMany(PaymentReport::class, 'cliente_id')->where('approved', false)->whereHas('payment_reports_movement', function ($query) {
            $query->latest();
        });
    }

    public function getFullNameAttribute()
    {
        return $this->nombre.' '.$this->nombre_segundo.' '.$this->apellido.' '.$this->apellido_segundo;
    }

    public function getFullDocumentAttribute()
    {
        return $this->tipo_documento->sigla.'-'.$this->documento;
    }

    public function getCountryIdAttribute()
    {
        return $this->city->country_id;
    }

    public function getPrestamosActivosAttribute()
    {

        if (count($this->Pretamos) > 0) {
            return $this->Pretamos->filter(function ($prestamo) {
                return  ! $prestamo->pagado && ! $prestamo->anulado && ! $prestamo->perdido;
            })->count();
        }

        return 0;

    }

    public function getInformesPendientesAttribute()
    {

        if (count($this->InformesPandientesActivos) > 0) {
            return $this->InformesPandientesActivos->filter(function ($InformesPandiente) {

                if (in_array($InformesPandiente->payment_reports_movement[0]->estatus, [1, 4])) {
                    return $InformesPandiente;
                }

            })->count();
        }

        return 0;

    }
}
