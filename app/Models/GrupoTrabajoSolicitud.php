<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $idgrupo_trabajo
 * @property int $user_id
 * @property string $estatus
 * @property string $origen
 * @property int|null $decidido_por
 * @property Carbon|null $decidido_at
 * @property Carbon|null $created_at
 */
class GrupoTrabajoSolicitud extends Model
{
    public const PENDIENTE = 'pendiente';

    public const APROBADA = 'aprobada';

    public const RECHAZADA = 'rechazada';

    /** Usuario nuevo que se registró con el código del grupo. */
    public const ORIGEN_REGISTRO = 'registro';

    /** Usuario existente que pidió unirse a otro grupo. */
    public const ORIGEN_CODIGO = 'codigo';

    protected $table = 'grupos_trabajos_solicitudes';

    protected $fillable = ['idgrupo_trabajo', 'user_id', 'estatus', 'origen', 'decidido_por', 'decidido_at'];

    protected $casts = [
        'decidido_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<GruposTrabajo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GruposTrabajo::class, 'idgrupo_trabajo');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
