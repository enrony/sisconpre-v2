<?php

use App\Http\Controllers\TipoPrestamoController;
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

Route::middleware(['auth', 'verified'])->get('/tipo_prestamo', [TipoPrestamoController::class, 'index'])->name('tipo_prestamo');

Route::prefix('/tipo_prestamo')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [TipoPrestamoController::class, 'store']);
    Route::delete('/{id}/{page}', [TipoPrestamoController::class, 'destroy']);
    Route::get('/tables', [TipoPrestamoController::class, 'tables']);
});
