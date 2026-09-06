<?php

use App\Http\Controllers\GruposTrabajoController;
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

Route::middleware(['auth', 'verified'])->get('/grupos_trabajo', [GruposTrabajoController::class, 'index'])->name('grupos_trabajo');

Route::prefix('/grupos_trabajo')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [GruposTrabajoController::class, 'store'])->middleware('permission:grupos_trabajo.registrar|grupos_trabajo.editar');
    Route::delete('/{id}/{page}', [GruposTrabajoController::class, 'destroy'])->middleware('permission:grupos_trabajo.eliminar');
    Route::get('/generateCode', [GruposTrabajoController::class, 'generateCode']);
    Route::get('/tables', [GruposTrabajoController::class, 'tables']);
});
