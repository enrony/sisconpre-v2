<?php

namespace Tests\Feature;

use App\Http\Requests\StoreFranquiciaRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * `FormRequest::authorize()` real (Fase 9): los requests de alta/edición de
 * maestros exigen `<recurso>.registrar` / `<recurso>.editar` de spatie.
 */
class FormRequestAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'prestamos_gilen',
        ]);
        DB::purge('mysql');

        if (! DB::table('users')->where('email', 'enrony@gmail.com')->exists()) {
            $this->markTestSkipped('BD local sin datos importados.');
        }
    }

    private function req(User $user, array $input): StoreFranquiciaRequest
    {
        $r = StoreFranquiciaRequest::create('/franquicias', 'PUT', $input);
        $r->setUserResolver(fn () => $user);

        return $r;
    }

    public function test_super_admin_autoriza_crear_y_editar(): void
    {
        $super = User::where('email', 'enrony@gmail.com')->firstOrFail();

        $this->assertTrue($this->req($super, ['id' => 0])->authorize());
        $this->assertTrue($this->req($super, ['id' => 5])->authorize());
    }

    public function test_usuario_sin_permiso_no_autoriza(): void
    {
        $cliente = User::where('email', 'ruiztovarmariaeugenia@gmail.com')->firstOrFail();

        $this->assertFalse($this->req($cliente, ['id' => 0])->authorize());
    }

    /** Con solo `franquicias.registrar`: puede crear (id 0) pero no editar (id > 0). */
    public function test_registrar_no_habilita_editar(): void
    {
        $cliente = User::where('email', 'ruiztovarmariaeugenia@gmail.com')->firstOrFail();

        $cliente->givePermissionTo('franquicias.registrar');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $cliente->unsetRelation('permissions')->unsetRelation('roles');

        try {
            $this->assertTrue($this->req($cliente, ['id' => 0])->authorize());
            $this->assertFalse($this->req($cliente, ['id' => 7])->authorize());
        } finally {
            $cliente->revokePermissionTo('franquicias.registrar');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
