<?php

namespace App\Services;

use App\Models\GruposTrabajoUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Grupo de trabajo activo del usuario: filtra TODA la información que ve
 * (ver `AlcanceCartera`) y define el país en el que opera. Es la membresía
 * con `grupos_trabajos_users.current_grupo = 1`.
 *
 * El super-usuario puede además ver "Todos los grupos" juntos (es su estado
 * por defecto, como antes de existir el selector); esa elección vive en la
 * sesión, no en la membresía, porque no corresponde a ningún grupo.
 */
class GrupoActivo
{
    private const SESION_TODOS = 'grupo_activo.todos';

    public static function esSuperUsuario(?User $user): bool
    {
        return (bool) $user?->hasRole('super-admin');
    }

    /** El super-usuario está mirando todos los grupos juntos. */
    public static function verTodos(?User $user): bool
    {
        return self::esSuperUsuario($user) && (bool) session(self::SESION_TODOS, true);
    }

    /**
     * Grupos a los que pertenece el usuario (membresías activas), con su
     * ciudad y país para el selector.
     *
     * @return list<array{gtu_id: int, grupo_id: int, nombre: string, ciudad: string|null, pais: string|null, pais_codigo: string|null, activo: bool}>
     */
    public static function opciones(User $user): array
    {
        return array_values(DB::table('grupos_trabajos_users as gtu')
            ->join('grupos_trabajos as g', 'g.id', '=', 'gtu.idgrupo_trabajo')
            ->leftJoin('cities as ci', 'ci.id', '=', 'g.city_id')
            ->leftJoin('countries as co', 'co.id', '=', 'ci.country_id')
            ->where('gtu.iduser', $user->id)
            ->where('gtu.estatus', 1)
            ->where('g.estatus', 1)
            ->orderBy('g.nombre')
            ->get(['gtu.id as gtu_id', 'g.id as grupo_id', 'g.nombre', 'ci.nombre as ciudad', 'co.Name as pais', 'co.Code2 as pais_codigo', 'gtu.current_grupo'])
            ->map(fn (object $r): array => [
                'gtu_id' => (int) $r->gtu_id,
                'grupo_id' => (int) $r->grupo_id,
                'nombre' => (string) $r->nombre,
                'ciudad' => $r->ciudad !== null ? (string) $r->ciudad : null,
                'pais' => $r->pais !== null ? (string) $r->pais : null,
                'pais_codigo' => $r->pais_codigo !== null ? (string) $r->pais_codigo : null,
                'activo' => (bool) $r->current_grupo,
            ])
            ->all());
    }

    /**
     * Activa una de las membresías del usuario (o "Todos los grupos" para el
     * super-usuario, con `$gtuId = null`). Devuelve false si no corresponde.
     */
    public static function cambiar(User $user, ?int $gtuId): bool
    {
        if ($gtuId === null) {
            if (! self::esSuperUsuario($user)) {
                return false;
            }

            session([self::SESION_TODOS => true]);

            return true;
        }

        $membresia = GruposTrabajoUser::where('id', $gtuId)->where('iduser', $user->id)->where('estatus', 1)->first();

        if (! $membresia) {
            return false;
        }

        // Las dos escrituras van por query: si la elegida ya era la activa, el modelo
        // leído antes "no ve cambios" y no guardaría, dejando al usuario sin grupo.
        DB::transaction(function () use ($user, $membresia): void {
            GruposTrabajoUser::where('iduser', $user->id)->where('id', '!=', $membresia->id)->update(['current_grupo' => 0]);
            GruposTrabajoUser::whereKey($membresia->id)->update(['current_grupo' => 1]);
        });

        if (self::esSuperUsuario($user)) {
            session([self::SESION_TODOS => false]);
        }

        return true;
    }
}
