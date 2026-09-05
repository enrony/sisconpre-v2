<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GruposTrabajoUser extends Model
{
    use HasFactory;

    protected $table = 'grupos_trabajos_users';

    protected $fillable = [
        'idgrupo_trabajo', 'iduser', 'estatus', 'current_grupo',
    ];

    public function grupo_trabajo()
    {
        return $this->belongsTo(GruposTrabajo::class);
    }
}
