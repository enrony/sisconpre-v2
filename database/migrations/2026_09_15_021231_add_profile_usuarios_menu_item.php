<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $parentId = DB::table('menu_items')->where('key', 'mantenimiento')->value('id');

        DB::table('menu_items')->insert([
            'parent_id' => $parentId,
            'key' => 'profile_usuarios',
            'label' => 'Usuarios y roles',
            'icon' => null,
            'route' => 'profile.usuarios',
            'permission' => 'profile.listar',
            'position' => 2,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')->where('key', 'profile_usuarios')->delete();
    }
};
