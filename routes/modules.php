<?php

use App\Http\Controllers\ModulesController;
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

Route::middleware(['auth', 'verified'])->get('/modules', [ModulesController::class, 'index'])->name('modules');
Route::prefix('/modules')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/modulesMenues', [ModulesController::class, 'modulesMenues']);
    Route::get('/lista_modules', [ModulesController::class, 'records']);
    Route::get('/record/{id}', [ModulesController::class, 'record']);
    Route::post('/record', [ModulesController::class, 'store']);
    Route::get('/esquema', [ModulesController::class, 'esquema']);
});
