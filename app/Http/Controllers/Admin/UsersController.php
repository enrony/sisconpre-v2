<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

/**
 * Asignación de roles spatie a usuarios (reemplaza `users_profiles` del legado;
 * ver PLAN_MIGRACION.md §11).
 */
class UsersController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::query()
            ->select('id', 'name', 'email')
            ->with('roles:id,name')
            ->when($request->term, function ($q, $term) {
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->roles->pluck('name')->values(),
            ]);

        return Inertia::render('admin/Usuarios', [
            'usuarios' => $usuarios,
            'roles' => Role::orderBy('name')->pluck('name'),
            'messages' => __('messages'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Roles actualizados.');

        return Redirect::route('profile.usuarios', ['page' => $request->input('page', 1)]);
    }
}
