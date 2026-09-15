<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\User;
use App\Models\UserCountry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Asignación de roles spatie y de países a usuarios (reemplaza `users_profiles`
 * del legado; ver PLAN_MIGRACION.md §11).
 *
 * "Países" acá significa en qué países puede ESTE usuario crear un grupo de
 * trabajo nuevo (`user_countries`) — no en qué país opera: eso ahora se resuelve
 * desde el grupo de trabajo activo del usuario (`Controller::obtenerPaisActivo()`).
 */
class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $usuarios = User::query()
            ->select('id', 'name', 'email')
            ->with(['roles:id,name', 'ownedUserCountry:id,user_id,country_id'])
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
                'paises' => $u->ownedUserCountry->pluck('country_id')->values(),
            ]);

        return Inertia::render('admin/Usuarios', [
            'usuarios' => $usuarios,
            'roles' => Role::orderBy('name')->pluck('name'),
            'paises' => Country::where('estatus', 1)->orderBy('Name')->get(['id', 'Name']),
            'messages' => __('messages'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'paises' => ['sometimes', 'array'],
            'paises.*' => ['string', 'exists:countries,id'],
        ]);

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles']);
            $mensaje = 'Roles actualizados.';
        }

        if (array_key_exists('paises', $data)) {
            UserCountry::where('user_id', $user->id)
                ->whereNotIn('country_id', $data['paises'])
                ->delete();

            foreach ($data['paises'] as $countryId) {
                UserCountry::firstOrCreate([
                    'user_id' => $user->id,
                    'country_id' => $countryId,
                ]);
            }

            $mensaje = 'Países actualizados.';
        }

        session()->flash('flash.type', 'success');
        session()->flash('flash.message', $mensaje ?? 'Actualizado.');

        return Redirect::route('profile.usuarios', ['page' => $request->input('page', 1)]);
    }
}
