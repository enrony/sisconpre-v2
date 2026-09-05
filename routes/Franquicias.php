<?php

use App\Http\Controllers\FranquiciaController;
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

Route::middleware(['auth', 'verified'])->get('/franquicias', [FranquiciaController::class, 'index'])->name('franquicias');

Route::prefix('/franquicias')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [FranquiciaController::class, 'store']);
    Route::delete('/{id}/{page}', [FranquiciaController::class, 'destroy']);
    Route::get('/tables', [FranquiciaController::class, 'tables']);
    Route::get('/record/{id}', [FranquiciaController::class, 'record']);
});
