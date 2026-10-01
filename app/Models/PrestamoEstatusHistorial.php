<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoEstatusHistorial extends Model
{
    protected $table = 'prestamos_estatus_historial';

    protected $fillable = [
        'prestamo_id',
        'estatus_anterior',
        'estatus_nuevo',
        'motivo',
        'user_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
