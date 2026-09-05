<?php

namespace App\Http\Controllers;

use App\Http\Traits\generalsTrait;
use App\Models\GruposTrabajoUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, generalsTrait, ValidatesRequests;

    /**
     * Grupo de trabajo activo del usuario autenticado (multi-tenencia por grupo).
     */
    public static function obtenerGrupoTrabajo(): ?GruposTrabajoUser
    {
        return GruposTrabajoUser::query()
            ->select('id', 'idgrupo_trabajo')
            ->where('iduser', auth()->id())
            ->where('current_grupo', 1)
            ->first();
    }

    /**
     * Todos los grupos de trabajo asignados a un usuario.
     */
    public function obtenerGruposTrabajos(int $id)
    {
        return GruposTrabajoUser::query()
            ->select('id', 'idgrupo_trabajo', 'estatus')
            ->where('iduser', $id)
            ->get();
    }
}
