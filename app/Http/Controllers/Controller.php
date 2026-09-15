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
     * País activo del usuario autenticado (multi-tenencia por país): se resuelve
     * desde su **grupo de trabajo activo** (`current_grupo=1` → `grupos_trabajos.city_id`
     * → `cities.country_id`), no desde una asignación de país aparte — el país de
     * un usuario es el país de su grupo de trabajo (decisión 2026-09-14; `user_countries`
     * pasó a significar "en qué países puede este usuario crear un grupo de trabajo
     * nuevo", no "en qué país opera").
     *
     * `null` = sin restricción de país: superusuario, usuario sin grupo de trabajo
     * activo todavía, o grupo sin ciudad asignada (huecos de datos que hay que
     * cerrar aparte, no algo que este método deba inventar).
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

        return GruposTrabajoUser::query()
            ->join('grupos_trabajos', 'grupos_trabajos.id', '=', 'grupos_trabajos_users.idgrupo_trabajo')
            ->join('cities', 'cities.id', '=', 'grupos_trabajos.city_id')
            ->where('grupos_trabajos_users.iduser', $userId)
            ->where('grupos_trabajos_users.current_grupo', 1)
            ->value('cities.country_id');
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
