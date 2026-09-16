<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC propio ("modules"/"profiles") ya consolidado en spatie/laravel-permission
 * + `menu_items` (PLAN_MIGRACION.md §11, ejecutado con `rbac:sync-from-legacy`
 * en su momento). Estas 7 tablas quedaron sin ningún lector desde entonces —
 * el único punto que todavía dependía de ellas era `Controller::isSuperUsuario()`
 * (ahora resuelto contra el rol spatie `super-admin`, ver generalsTrait.php).
 *
 * Irreversible a propósito: no tiene sentido recrear el esquema legado vacío,
 * los datos ya viven en spatie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('modules_actions_profiles');
        Schema::dropIfExists('users_profiles');
        Schema::dropIfExists('modules_actions');
        Schema::dropIfExists('modules_relations');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('modules');

        Schema::enableForeignKeyConstraints();

        // Único ítem del menú que apuntaba a la ruta /modules ya eliminada.
        DB::table('menu_items')->where('key', 'modules')->delete();
    }

    public function down(): void
    {
        // Intencional: ver docblock de la clase.
    }
};
