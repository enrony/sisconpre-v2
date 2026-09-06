<?php

namespace App\Http\Requests\Concerns;

/**
 * Autorización real para los FormRequest de alta/edición de maestros y
 * administración. El mismo request atiende crear y editar (`id > 0` → editar),
 * así que se exige el permiso spatie que corresponda: `<recurso>.registrar`
 * o `<recurso>.editar`. El super-admin pasa por `Gate::before`.
 */
trait AuthorizesResourceWrite
{
    protected function canWriteResource(string $resource): bool
    {
        $ability = $this->integer('id') > 0
            ? "{$resource}.editar"
            : "{$resource}.registrar";

        return (bool) $this->user()?->can($ability);
    }
}
