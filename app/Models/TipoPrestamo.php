<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPrestamo extends Model
{
    use HasFactory;

    protected $with = ['frecuencias'];

    protected $fillable = [
        'tipo_frecuencia_prestamo_id', 'descripcion', 'active', 'por_defecto', 'cantidad', 'grupos_trabajos_user_id', 'estatus',
    ];

    public function frecuencias()
    {
        return $this->belongsTo(TipoFrecuenciaPrestamo::class, 'tipo_frecuencia_prestamo_id');
    }
}
