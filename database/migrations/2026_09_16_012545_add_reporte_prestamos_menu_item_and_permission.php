<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('reporte_prestamos.listar', 'web');

        $parentId = DB::table('menu_items')->where('key', 'reportes')->value('id');

        DB::table('menu_items')->insert([
            'parent_id' => $parentId,
            'key' => 'reporte_prestamos',
            'label' => 'Préstamos',
            'icon' => null,
            'route' => 'reporte_prestamos',
            'permission' => 'reporte_prestamos.listar',
            'position' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('key', 'reporte_prestamos')->delete();
        Permission::where('name', 'reporte_prestamos.listar')->delete();
    }
};
