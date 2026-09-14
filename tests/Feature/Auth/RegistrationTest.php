<?php

namespace Tests\Feature\Auth;

use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register_with_a_valid_grupo_trabajo_code()
    {
        $grupo = GruposTrabajo::create([
            'nombre' => 'Grupo de prueba',
            'code' => 'ABC123',
            'estatus' => 1,
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            // en minúscula a propósito: el código se normaliza a mayúscula
            'codigo_grupo_trabajo' => 'abc123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertDatabaseHas('grupos_trabajos_users', [
            'iduser' => $user->id,
            'idgrupo_trabajo' => $grupo->id,
            'current_grupo' => 1,
        ]);
        $this->assertCount(0, $user->getRoleNames(), 'no se asigna ningún rol automáticamente al registrarse');
    }

    public function test_registration_fails_without_a_valid_grupo_trabajo_code()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'sin-codigo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'codigo_grupo_trabajo' => 'NOEXISTE',
        ]);

        $response->assertSessionHasErrors('codigo_grupo_trabajo');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'sin-codigo@example.com']);
        $this->assertDatabaseCount(GruposTrabajoUser::class, 0);
    }
}
