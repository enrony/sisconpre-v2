<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestamosEstatu extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'action_description',
        'style',
        'finish_estatus',
        'motivo',
        'soporte',
        'grupos_trabajos_user_id',
        'estatus',
        'type_tag',
    ];

    protected $casts = [
        'type_tag' => 'object',
    ];
}
