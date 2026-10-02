<?php

namespace Tests\Feature\Auth;

use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\GrupoTrabajoSolicitud;
use App\Models\User;
use App\Notifications\SolicitudUnionGrupo;
use App\Notifications\SolicitudUnionGrupoResuelta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        $this->assertCount(0, $user->getRoleNames(), 'no se asigna ningún rol automáticamente al registrarse');
    }

    /**
     * Con el código no entra directo al grupo: el dueño recibe un correo y
     * aprueba su ingreso. Mientras tanto ve su solicitud en espera.
     */
    public function test_el_registro_con_codigo_espera_la_aprobacion_del_dueno()
    {
        Notification::fake();
        $dueno = User::factory()->create();
        $grupo = GruposTrabajo::create(['nombre' => 'Grupo de prueba', 'code' => 'ABC123', 'estatus' => 1]);
        $grupo->update(['grupos_trabajos_user_id' => GruposTrabajoUser::create([
            'iduser' => $dueno->id, 'idgrupo_trabajo' => $grupo->id, 'estatus' => 1, 'current_grupo' => 1,
        ])->id]);

        $this->post(route('register.store'), [
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'codigo_grupo_trabajo' => 'ABC123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $nuevo = User::where('email', 'nuevo@example.com')->firstOrFail();
        $this->assertDatabaseMissing('grupos_trabajos_users', ['iduser' => $nuevo->id]);
        $solicitud = GrupoTrabajoSolicitud::where('user_id', $nuevo->id)->firstOrFail();
        $this->assertSame(GrupoTrabajoSolicitud::PENDIENTE, $solicitud->estatus);
        $this->assertSame(GrupoTrabajoSolicitud::ORIGEN_REGISTRO, $solicitud->origen);
        Notification::assertSentTo($dueno, SolicitudUnionGrupo::class);

        // El nuevo ve su solicitud en espera y ningún grupo; el dueño la ve para decidir.
        $this->get('/dashboard')->assertInertia(fn ($page) => $page
            ->has('misSolicitudes', 1)
            ->where('misSolicitudes.0.grupo', 'Grupo de prueba')
            ->has('misGrupos', 0));
        $this->actingAs($dueno)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->has('solicitudesPorDecidir', 1)
            ->where('solicitudesPorDecidir.0.registro', true));

        // Al aprobarlo entra al grupo y queda como su grupo activo.
        $this->actingAs($dueno)->putJson("/grupos/solicitudes/{$solicitud->id}/aprobar")->assertOk();
        $this->assertDatabaseHas('grupos_trabajos_users', [
            'iduser' => $nuevo->id, 'idgrupo_trabajo' => $grupo->id, 'estatus' => 1, 'current_grupo' => 1,
        ]);
        Notification::assertSentTo($nuevo, SolicitudUnionGrupoResuelta::class);
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
