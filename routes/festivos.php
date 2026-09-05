<?php

use App\Http\Controllers\CountryHolidayController;
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

$controlador = CountryHolidayController::class;

Route::middleware(['auth', 'verified'])->get('/festivos', [$controlador, 'index'])->name('festivos');

Route::prefix('/festivos')->middleware(['auth', 'verified'])->group(function () use ($controlador) {
    Route::put('/', [$controlador, 'store']);
    Route::delete('/{id}/{page}', [$controlador, 'destroy']);
    Route::get('/tables', [$controlador, 'tables']);
    Route::get('/record/{id}', [$controlador, 'record']);
});
