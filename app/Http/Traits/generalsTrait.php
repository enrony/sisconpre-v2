<?php

namespace App\Http\Traits;

use App\Models\User;

trait generalsTrait
{
    /**
     * Superusuario = tiene el rol spatie `super-admin` (antes se resolvía
     * contra `users_profiles`/`profiles.su`, tablas del RBAC legado ya
     * eliminadas — ver PLAN_MIGRACION.md §11).
     */
    public static function isSuperUsuario(int $id): bool
    {
        return (bool) User::find($id)?->hasRole('super-admin');
    }
}
