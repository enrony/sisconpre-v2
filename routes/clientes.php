<?php

use App\Http\Controllers\ClientesController;
use Illuminate\Support\Facades\Route;

/*
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('/clientes')->middleware(['auth', 'verified'])->group(function () {
    Route::post('/lista-clientes', [ClientesController::class, 'listaClientes2']);
    Route::get('/lista-clientes-json', [ClientesController::class, 'listaClientesJson']);
    Route::get('/lista-clientes-json-basic', [ClientesController::class, 'listaClientesJsonBasic']);
    Route::get('/record/{id}', [ClientesController::class, 'record']);
    Route::get('/tables', [ClientesController::class, 'tables']);
});
