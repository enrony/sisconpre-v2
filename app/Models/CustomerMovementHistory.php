<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMovementHistory extends Model
{
    use HasFactory;

    public $fillable = [
        'cliente_id',
        'type_movement_id',
        'payment_report_id',
        'amount',
        'date_movement',
        'grupos_trabajos_user_id',
        'estatus',
    ];

    public function type_movement()
    {
        return $this->belongsTo(TypesMovement::class);
    }
}
