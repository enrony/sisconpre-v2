<?php

namespace App\Http\Controllers;

use App\Http\Traits\generalsTrait;
use App\Models\GruposTrabajoUser;
use App\Models\UserCountry;
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
     * País activo del usuario autenticado (multi-tenencia por país): `current_country`
     * si está marcado, si no el país `principal`, si no el primero asignado.
     * `null` = sin restricción de país (superusuario, o usuario sin países asignados
     * todavía — ver el hueco de asignación documentado en PLAN_MIGRACION.md).
     */
    public static function obtenerPaisActivo(): ?string
    {
        $userId = auth()->user()?->id;

        if ($userId === null) {
            return null;
        }

        if (static::isSuperUsuario($userId)) {
            return null;
        }

        $userCountry = UserCountry::query()
            ->select('country_id')
            ->where('user_id', $userId)
            ->where('estatus', 1)
            ->orderByDesc('current_country')
            ->orderByDesc('principal')
            ->orderBy('id')
            ->first();

        return $userCountry?->country_id;
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
