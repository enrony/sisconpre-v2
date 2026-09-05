<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypePaymentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'estatus', 'grupos_trabajos_user_id', 'require_approval',
    ];

    protected $casts = [
        'require_approval' => 'boolean',
    ];
}
