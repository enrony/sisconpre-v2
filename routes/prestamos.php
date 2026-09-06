<?php

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\PrestamosController;
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

Route::prefix('/user')->middleware(['auth', 'verified'])->group(function () {
    Route::post('/lista-clientes', [ClientesController::class, 'listaClientes2']);
    Route::put('/actualizaCliente', [ClientesController::class, 'actualizaCliente'])
        ->middleware('permission:clientes.registrar|clientes.editar');
    Route::get('/consultaPrestamos/{id_cliente}', [ClientesController::class, 'consultaPrestamos']);
});

Route::prefix('/prestamos')->middleware(['auth'])->group(function () {
    // return Inertia\Inertia::render('Prestamos');
    Route::get('', [PrestamosController::class, 'index'])->name('prestamos');
    Route::get('/tables', [PrestamosController::class, 'tables']);
    Route::put('/', [PrestamosController::class, 'store'])->middleware('permission:prestamos.registrar');
    // `/prestamos/obtenerPrestamosActivos/{cliente}` vive en routes/shared.php
    // (lo consume "Informar un pago" del módulo de Informes de pago).
    Route::post('/records', [PrestamosController::class, 'records']);
    Route::get('/recordsEstados', [PrestamosController::class, 'recordsEstados']);
    Route::put('/{prestamo}/pausar-recargo', [PrestamosController::class, 'togglePausaRecargo'])
        ->middleware('permission:prestamos.editar');
});

// La ruta GET /clientes vive ahora en routes/clientes.php (gate `clientes.listar`).
