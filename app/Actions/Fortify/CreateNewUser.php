<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Clientes;
use App\Models\GruposTrabajo;
use App\Models\User;
use App\Services\SolicitudesGrupo;
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
     * el grupo) al que este usuario pide unirse. Sin un código de un grupo activo
     * no se crea la cuenta — el registro público no queda abierto a cualquiera.
     * Con el código no entra directo: queda una solicitud que aprueba el dueño
     * del grupo (`SolicitudesGrupo`); hasta entonces no ve datos de ningún grupo.
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

        // No entra directo al grupo: el dueño recibe un correo y aprueba su ingreso.
        app(SolicitudesGrupo::class)->alRegistrarse($user, $grupo);

        // Si ya es cliente (ficha con su email), entra viendo solo su cartera.
        Clientes::vincularConUsuario($user);

        return $user;
    }
}
