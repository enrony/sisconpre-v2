<?php

use App\Http\Controllers\FrecuenciasController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth', 'verified'])->get('/frecuencias', [FrecuenciasController::class, 'index'])->name('frecuencias');

Route::prefix('/frecuencias')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [FrecuenciasController::class, 'store'])->middleware('permission:frecuencias.registrar|frecuencias.editar');
    Route::delete('/{id}/{page}', [FrecuenciasController::class, 'destroy'])->middleware('permission:frecuencias.eliminar');
    Route::get('/tables', [FrecuenciasController::class, 'tables']);
});
