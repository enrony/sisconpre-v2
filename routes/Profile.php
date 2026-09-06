<?php

use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Admin / RBAC (roles spatie + asignación a usuarios)
|--------------------------------------------------------------------------
|
| Cargado por `bootstrap/app.php` bajo `permission:profile.listar`.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [RolesController::class, 'index'])->name('profile');
    Route::put('/profile', [RolesController::class, 'store'])
        ->middleware('permission:profile.editar');
    Route::delete('/profile/roles/{role}', [RolesController::class, 'destroy'])
        ->middleware('permission:profile.eliminar');

    Route::get('/profile/usuarios', [UsersController::class, 'index'])->name('profile.usuarios');
    Route::put('/profile/usuarios/{user}', [UsersController::class, 'update'])
        ->middleware('permission:profile.editar');
});
