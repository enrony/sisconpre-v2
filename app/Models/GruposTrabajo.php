<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GruposTrabajo extends Model
{
    use HasFactory;

    public $fillable = ['nombre', 'grupos_trabajos_user_id', 'code', 'reference', 'city_id'];

    protected $with = ['ciudad'];

    public function grupo_trabajo_user()
    {
        return $this->belongsTo(GruposTrabajoUser::class);
    }

    public function ciudad()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
