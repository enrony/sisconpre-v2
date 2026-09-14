<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * `codigo_grupo_trabajo` es obligatorio: identifica el grupo de trabajo
     * (`grupos_trabajos.code`, generado por `GruposTrabajoController` al crear
     * el grupo) al que este usuario se une. Sin un código de un grupo activo
     * no se crea la cuenta — el registro público no queda abierto a cualquiera.
     * No se le asigna ningún rol acá a propósito: queda pendiente de que un
     * administrador se lo asigne desde Usuarios/Roles.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $codigo = Str::upper(trim((string) ($input['codigo_grupo_trabajo'] ?? '')));

        Validator::make([...$input, 'codigo_grupo_trabajo' => $codigo], [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'codigo_grupo_trabajo' => [
                'required',
                'string',
                Rule::exists('grupos_trabajos', 'code')->where('estatus', 1),
            ],
        ], [
            'codigo_grupo_trabajo.exists' => 'No encontramos ese código de grupo de trabajo. Verificalo con tu supervisor.',
        ], [
            'codigo_grupo_trabajo' => 'código de grupo de trabajo',
        ])->validate();

        $grupo = GruposTrabajo::where('code', $codigo)->where('estatus', 1)->firstOrFail();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        GruposTrabajoUser::create([
            'iduser' => $user->id,
            'idgrupo_trabajo' => $grupo->id,
            'estatus' => 1,
            'current_grupo' => 1,
        ]);

        return $user;
    }
}
