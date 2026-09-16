<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GruposTrabajo extends Model
{
    use HasFactory;

    public $fillable = ['nombre', 'grupos_trabajos_user_id', 'code', 'reference', 'city_id'];

    protected $with = ['ciudad'];

    /**
     * @return BelongsTo<GruposTrabajoUser, $this>
     */
    public function grupo_trabajo_user(): BelongsTo
    {
        return $this->belongsTo(GruposTrabajoUser::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
