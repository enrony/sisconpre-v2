<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GruposTrabajoUser extends Model
{
    use HasFactory;

    protected $table = 'grupos_trabajos_users';

    protected $fillable = [
        'idgrupo_trabajo', 'iduser', 'estatus', 'current_grupo',
    ];

    /**
     * @return BelongsTo<GruposTrabajo, $this>
     */
    public function grupo_trabajo(): BelongsTo
    {
        return $this->belongsTo(GruposTrabajo::class, 'idgrupo_trabajo');
    }
}
