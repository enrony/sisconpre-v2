<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Roles de personal que hoy ven la cartera: la siguen viendo, ahora acotada a sus grupos. */
    private const ROLES_PERSONAL = ['Prestamista V', 'Prestamista S/V', 'Administrador', 'Super Usuario'];

    /**
     * Alcance de la cartera (ver `App\Services\AlcanceCartera`): el personal con
     * `cartera.ver-grupo` ve lo de sus grupos de trabajo; un usuario vinculado a
     * una ficha de cliente (`clientes.user_id`) ve solo lo suyo.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->unique()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Vínculo inicial por email: solo cuando el email identifica a una única ficha.
        $emails = DB::table('clientes')
            ->whereNotNull('email')->where('email', '!=', '')
            ->selectRaw('lower(email) as email, count(*) as c, min(id) as id')
            ->groupByRaw('lower(email)')
            ->get();

        foreach ($emails as $fila) {
            if ((int) $fila->c !== 1) {
                continue;
            }

            $userId = DB::table('users')->whereRaw('lower(email) = ?', [$fila->email])->value('id');

            if ($userId) {
                DB::table('clientes')->where('id', $fila->id)->update(['user_id' => $userId]);
            }
        }

        $permiso = Permission::findOrCreate('cartera.ver-grupo', 'web');
        Role::whereIn('name', self::ROLES_PERSONAL)->get()->each(fn (Role $r) => $r->givePermissionTo($permiso));
    }

    public function down(): void
    {
        Permission::where('name', 'cartera.ver-grupo')->delete();

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
