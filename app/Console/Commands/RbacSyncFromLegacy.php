<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Permission as Ability;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Consolida el RBAC propio del sistema legado (`modules` / `actions` /
 * `modules_actions` / `modules_actions_profiles` / `profiles` / `users_profiles`)
 * en `spatie/laravel-permission`, y arma el árbol de `menu_items`.
 *
 * Requiere que esas tablas legadas ya tengan datos (`php artisan legacy:import-data`).
 * Idempotente. Ver PLAN_MIGRACION.md §11.
 */
class RbacSyncFromLegacy extends Command
{
    protected $signature = 'rbac:sync-from-legacy';

    protected $description = 'Migra el RBAC propio (modules/profiles) a spatie y genera menu_items';

    private const GUARD = 'web';

    /** Claves de módulo que son solo agrupadores de menú, sin permisos. */
    private const NON_PERMISSIONABLE = ['logout'];

    public function handle(): int
    {
        $modules = DB::table('modules')->get()->keyBy('id');
        $actions = DB::table('actions')->get()->keyBy('id');

        if ($modules->isEmpty() || $actions->isEmpty()) {
            $this->error('Faltan datos en `modules` / `actions`. Corre primero `php artisan legacy:import-data`.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($modules, $actions) {
            $this->syncPermissions($modules, $actions);
            $this->syncRoles();
            $this->syncGrants($modules, $actions);
            $this->syncUsers();
            $this->buildMenu($modules);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info(sprintf(
            'RBAC: %d permisos, %d roles, %d menu_items.',
            Permission::count(),
            Role::count(),
            DB::table('menu_items')->count(),
        ));

        return self::SUCCESS;
    }

    /** ¿La clave de módulo admite permisos (no es agrupador de menú)? */
    private function permissionable(?object $module): bool
    {
        return $module !== null
            && filled($module->clave)
            && $module->route !== '#'
            && ! in_array($module->clave, self::NON_PERMISSIONABLE, true);
    }

    private function abilitySlug(?object $action): ?string
    {
        return $action ? (Ability::BY_CODE[$action->code] ?? Str::slug($action->name)) : null;
    }

    /**
     * Un permiso por cada `modules_actions` de un módulo permisionable,
     * excepto "Acceso Total" (code 01), que se expande al conceder.
     */
    private function syncPermissions(mixed $modules, mixed $actions): void
    {
        $created = 0;

        foreach (DB::table('modules_actions')->get() as $ma) {
            $module = $modules->get($ma->modules_id);
            $action = $actions->get($ma->action_id);

            if (! $this->permissionable($module) || $action === null || $action->code === '01') {
                continue;
            }

            Permission::findOrCreate(
                Ability::name($module->clave, $this->abilitySlug($action)),
                self::GUARD,
            );
            $created++;
        }

        $this->info("  permisos procesados: {$created}");
    }

    private function syncRoles(): void
    {
        Role::findOrCreate('super-admin', self::GUARD);

        foreach (DB::table('profiles')->get() as $profile) {
            Role::findOrCreate($profile->name, self::GUARD);
        }

        $this->info('  roles: super-admin + '.DB::table('profiles')->count().' perfiles');
    }

    private function syncGrants(mixed $modules, mixed $actions): void
    {
        $profiles = DB::table('profiles')->get()->keyBy('id');
        $modulesActions = DB::table('modules_actions')->get()->keyBy('id');

        // limpiar concesiones previas de los roles de perfil (idempotencia)
        foreach ($profiles as $profile) {
            Role::findByName($profile->name, self::GUARD)->syncPermissions([]);
        }

        // perfiles admin => todos los permisos
        foreach ($profiles as $profile) {
            if ($profile->admin && ! $profile->su) {
                Role::findByName($profile->name, self::GUARD)->syncPermissions(Permission::all());
            }
        }

        foreach (DB::table('modules_actions_profiles')->get() as $map) {
            $profile = $profiles->get($map->profiles_id);
            $ma = $modulesActions->get($map->modules_actions_id);

            if ($profile === null || $ma === null) {
                continue;
            }

            $module = $modules->get($ma->modules_id);
            $action = $actions->get($ma->action_id);

            if (! $this->permissionable($module) || $action === null) {
                continue;
            }

            $role = Role::findByName($profile->name, self::GUARD);

            if ($action->code === '01') { // Acceso Total => todo el módulo
                $perms = Permission::where('name', 'like', $module->clave.'.%')->get();
                if ($perms->isNotEmpty()) {
                    $role->givePermissionTo($perms);
                }

                continue;
            }

            $role->givePermissionTo(Ability::name($module->clave, $this->abilitySlug($action)));
        }

        $this->info('  concesiones aplicadas desde modules_actions_profiles');
    }

    private function syncUsers(): void
    {
        $profiles = DB::table('profiles')->get()->keyBy('id');

        foreach (DB::table('users_profiles')->get() as $up) {
            $user = User::find($up->users_id);
            $profile = $profiles->get($up->profiles_id);

            if ($user === null || $profile === null) {
                continue;
            }

            $roles = [$profile->name];
            if ($profile->su) {
                $roles[] = 'super-admin';
            }

            $user->syncRoles($roles);
            $this->info("  usuario {$user->email} => ".implode(', ', $roles));
        }
    }

    /**
     * Arma `menu_items` desde `modules` + `modules_relations`.
     * `permission` = `<clave>.listar` si ese permiso existe.
     */
    private function buildMenu(mixed $modules): void
    {
        DB::table('menu_items')->delete();

        $listar = Permission::where('name', 'like', '%.'.Ability::LISTAR)->pluck('name')->flip();
        $idByKey = [];

        foreach ($modules as $m) {
            if (! $m->visible_menu || blank($m->clave)) {
                continue;
            }

            $permission = $listar->has($m->clave.'.'.Ability::LISTAR) ? $m->clave.'.'.Ability::LISTAR : null;

            $idByKey[$m->clave] = DB::table('menu_items')->insertGetId([
                'parent_id' => null,
                'key' => $m->clave,
                'label' => $m->name,
                'icon' => $m->icon,
                'route' => $m->route === '#' ? null : $m->route,
                'permission' => $permission,
                'position' => $m->order,
                'is_active' => (bool) $m->active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('modules_relations')->get() as $rel) {
            $father = $modules->get($rel->modules_father_id);
            $son = $modules->get($rel->modules_son_id);

            if ($father === null || $son === null) {
                continue;
            }

            if (isset($idByKey[$son->clave], $idByKey[$father->clave])) {
                DB::table('menu_items')->where('id', $idByKey[$son->clave])
                    ->update(['parent_id' => $idByKey[$father->clave]]);
            }
        }

        $this->info('  menu_items: '.count($idByKey).' ítems');
    }
}
