<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientesGruposTrabajosUsers extends Model
{
    use HasFactory;

    protected $fillable = [
        'clientes_id', 'grupos_trabajos_user_id',
    ];
}
