<?php

use App\Http\Controllers\TiposDocumentosController;
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

Route::middleware(['auth', 'verified'])->get('/tipo_documentos', [TiposDocumentosController::class, 'index'])->name('tipo_documentos');

Route::prefix('/tipo_documentos')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [TiposDocumentosController::class, 'store'])->middleware('permission:tipo_documentos.registrar|tipo_documentos.editar');
    Route::delete('/{id}/{page}', [TiposDocumentosController::class, 'destroy'])->middleware('permission:tipo_documentos.eliminar');
    Route::get('/tables', [TiposDocumentosController::class, 'tables']);
    Route::get('/record/{id}', [TiposDocumentosController::class, 'record']);
});
