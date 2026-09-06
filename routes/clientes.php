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
    Route::put('/', [ClientesController::class, 'actualizaCliente']);
    Route::delete('/{id}/{page}', [ClientesController::class, 'destroy']);
    Route::post('/lista-clientes', [ClientesController::class, 'listaClientes2']);
    Route::get('/lista-clientes-json', [ClientesController::class, 'listaClientesJson']);
    Route::get('/lista-clientes-json-basic', [ClientesController::class, 'listaClientesJsonBasic']);
    Route::get('/record/{id}', [ClientesController::class, 'record']);
    Route::get('/tables', [ClientesController::class, 'tables']);
});
