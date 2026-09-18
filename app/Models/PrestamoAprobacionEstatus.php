<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestamoAprobacionEstatus extends Model
{
    protected $table = 'prestamos_aprobacion_estatus';

    protected $fillable = [
        'description',
        'type_tag',
    ];

    protected $casts = [
        'type_tag' => 'object',
    ];
}
