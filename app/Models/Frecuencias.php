<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frecuencias extends Model
{
    use HasFactory;

    public $fillable = ['nombre', 'tipo_frecuencia_prestamo_id', 'cantidad', 'grupos_trabajos_user_id'];

    public function tipo()
    {
        return $this->belongsTo(TipoFrecuenciaPrestamo::class, 'tipo_frecuencia_prestamo_id');
    }
}
