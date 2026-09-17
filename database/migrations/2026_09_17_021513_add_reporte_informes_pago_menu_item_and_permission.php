<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('reporte_informes_pago.listar', 'web');

        $parentId = DB::table('menu_items')->where('key', 'reportes')->value('id');

        DB::table('menu_items')->insert([
            'parent_id' => $parentId,
            'key' => 'reporte_informes_pago',
            'label' => 'Informes de pago',
            'icon' => null,
            'route' => 'reporte_informes_pago',
            'permission' => 'reporte_informes_pago.listar',
            'position' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menu_items')->where('key', 'reporte_informes_pago')->delete();
        Permission::where('name', 'reporte_informes_pago.listar')->delete();
    }
};
