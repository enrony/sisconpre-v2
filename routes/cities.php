<?php

use App\Http\Controllers\CityController;
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

$controlador = CityController::class;

Route::middleware(['auth', 'verified'])->get('/cities', [$controlador, 'index'])->name('cities');

Route::prefix('/cities')->middleware(['auth', 'verified'])->group(function () use ($controlador) {
    Route::put('/', [$controlador, 'store'])->middleware('permission:cities.registrar|cities.editar');
    Route::delete('/{id}/{page}', [$controlador, 'destroy'])->middleware('permission:cities.eliminar');
    Route::get('/tables', [$controlador, 'tables']);
    Route::get('/record/{id}', [$controlador, 'record']);
});
