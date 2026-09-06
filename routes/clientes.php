<?php

use App\Http\Controllers\ClientesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Clientes
|--------------------------------------------------------------------------
|
| Cargado por `bootstrap/app.php` dentro de un grupo con
| `middleware(['web','auth','verified','permission:clientes.listar'])`.
|
*/

Route::middleware(['auth', 'verified'])
    ->get('/clientes', [ClientesController::class, 'index'])
    ->name('clientes');

Route::prefix('/clientes')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [ClientesController::class, 'actualizaCliente'])
        ->middleware('permission:clientes.registrar|clientes.editar');
    Route::delete('/{id}/{page}', [ClientesController::class, 'destroy'])
        ->middleware('permission:clientes.eliminar');
    Route::post('/lista-clientes', [ClientesController::class, 'listaClientes2']);
    Route::get('/record/{id}', [ClientesController::class, 'record']);
    // `/clientes/tables`, `/clientes/lista-clientes-json[-basic]` viven en
    // routes/shared.php (los consumen Préstamos e Informes de pago, no solo Clientes).
});
