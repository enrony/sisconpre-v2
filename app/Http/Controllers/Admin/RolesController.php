<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Administración de roles spatie (reemplaza la pantalla "Perfiles" del legado;
 * ver PLAN_MIGRACION.md §11). Un rol = conjunto de permisos `"<modulo>.<habilidad>"`.
 */
class RolesController extends Controller
{
    /** Roles que no se pueden renombrar ni eliminar. */
    private const PROTEGIDOS = ['super-admin'];

    public function index(): Response
    {
        $roles = Role::query()
            ->withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'users_count' => $r->users_count,
                'permissions' => $r->permissions->pluck('name')->values(),
                'protegido' => in_array($r->name, self::PROTEGIDOS, true),
            ]);

        return Inertia::render('admin/Roles', [
            'roles' => $roles,
            'catalogo' => $this->catalogo(),
            'messages' => __('messages'),
        ]);
    }

    /**
     * Catálogo de permisos agrupados por módulo (el prefijo antes del punto).
     *
     * @return list<array{modulo: string, permisos: list<string>}>
     */
    private function catalogo(): array
    {
        return array_values(
            Permission::orderBy('name')->pluck('name')
                ->groupBy(fn (string $n) => explode('.', $n)[0])
                ->map(fn ($permisos, $modulo): array => [
                    'modulo' => (string) $modulo,
                    'permisos' => array_values($permisos->all()),
                ])
                ->all()
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'name' => [
                'required', 'string', 'max:125',
                Rule::unique('roles', 'name')->ignore($request->integer('id')),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $id = (int) ($data['id'] ?? 0);
        $role = $id > 0 ? Role::findOrFail($id) : new Role;

        if (in_array($role->name, self::PROTEGIDOS, true) && $role->name !== $data['name']) {
            return Redirect::back()->withErrors(['name' => 'Este rol no se puede renombrar.']);
        }

        $role->guard_name = 'web';
        $role->name = $data['name'];
        $role->save();

        $role->syncPermissions($data['permissions'] ?? []);

        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Rol guardado.');

        return Redirect::route('profile');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTEGIDOS, true)) {
            return Redirect::back()->withErrors(['role' => 'Este rol no se puede eliminar.']);
        }

        $role->delete();

        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Rol eliminado.');

        return Redirect::route('profile');
    }
}
