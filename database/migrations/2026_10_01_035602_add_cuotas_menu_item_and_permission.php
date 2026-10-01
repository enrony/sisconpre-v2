<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Pantalla "Cuotas pendientes": el prestamista ve lo que tiene que cobrar
     * (su grupo) y el cliente lo que tiene que pagar (lo suyo) — el alcance lo
     * decide `AlcanceCartera`, este permiso solo da acceso a la pantalla.
     */
    private const ROLES = [
        'Prestamista V', 'Prestamista S/V', 'Administrador', 'Super Usuario',
        'Cliente Verficado', 'Cliente S/V',
    ];

    public function up(): void
    {
        $permiso = Permission::findOrCreate('cuotas.listar', 'web');
        Role::whereIn('name', self::ROLES)->get()->each(fn (Role $r) => $r->givePermissionTo($permiso));

        DB::table('menu_items')->insert([
            'parent_id' => DB::table('menu_items')->where('key', 'procesos')->value('id'),
            'key' => 'cuotas',
            'label' => 'Cuotas pendientes',
            'icon' => null,
            'route' => 'cuotas',
            'permission' => 'cuotas.listar',
            'position' => 2,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('key', 'cuotas')->delete();
        Permission::where('name', 'cuotas.listar')->delete();
    }
};
