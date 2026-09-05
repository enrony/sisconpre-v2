<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypesMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'operation',
        'grupos_trabajos_user_id',
        'estatus',
    ];
}
