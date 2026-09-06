<?php

use App\Http\Controllers\ActionController;
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

Route::middleware(['auth', 'verified'])->get('/action', [ActionController::class, 'index'])->name('action');
Route::prefix('/action')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/lista', [ActionController::class, 'lista']);
    Route::get('/record/{id}', [ActionController::class, 'record']);
    Route::put('/', [ActionController::class, 'store'])->middleware('permission:action.registrar|action.editar');
});
