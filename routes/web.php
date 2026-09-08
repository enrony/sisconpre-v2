<?php

use Illuminate\Support\Facades\Route;

// La raíz lleva directo al panel. Si no hay sesión, el middleware 'auth'
// del grupo redirige a /login.
Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/shared.php';
