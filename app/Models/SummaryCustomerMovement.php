<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryCustomerMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'balance',
        'debit',
        'credit',
        'notes',
        'grupos_trabajos_user_id',
        'estatus',
    ];
}
