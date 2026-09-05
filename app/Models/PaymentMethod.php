<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'bank',
        'franchise',
        'reference',
        'box',
        'support',
        'estatus',
        'grupos_trabajos_user_id',
    ];

    protected $casts = [
        'bank' => 'boolean',
        'franchise' => 'boolean',
        'reference' => 'boolean',
        'box' => 'boolean',
        'support' => 'boolean',
    ];
}
