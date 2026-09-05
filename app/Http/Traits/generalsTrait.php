<?php

namespace App\Http\Traits;

use App\Models\ProfilesUsers;

trait generalsTrait
{
    public static function isSuperUsuario(int $id)
    {
        $ProfilesUsers = ProfilesUsers::WhereIsSU()->where('users_id', $id)->first();
        if ($ProfilesUsers) {
            return true;
        }

        return false;
    }
}
