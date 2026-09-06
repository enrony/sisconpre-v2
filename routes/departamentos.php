<?php

use App\Http\Controllers\DepartmentController;
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

$controlador = DepartmentController::class;

Route::middleware(['auth', 'verified'])->get('/departamentos', [$controlador, 'index'])->name('departamentos');

Route::prefix('/departamentos')->middleware(['auth', 'verified'])->group(function () use ($controlador) {
    Route::put('/', [$controlador, 'store'])->middleware('permission:departamentos.registrar|departamentos.editar');
    Route::delete('/{id}/{page}', [$controlador, 'destroy'])->middleware('permission:departamentos.eliminar');
    Route::get('/tables', [$controlador, 'tables']);
    Route::get('/record/{id}', [$controlador, 'record']);
});
