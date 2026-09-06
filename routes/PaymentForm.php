<?php

use App\Http\Controllers\PaymentFormController;
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

Route::middleware(['auth', 'verified'])->get('/payment_forms', [PaymentFormController::class, 'index'])->name('payment_forms');

Route::prefix('/payment_forms')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [PaymentFormController::class, 'store'])->middleware('permission:payment_forms.registrar|payment_forms.editar');
    Route::delete('/{id}/{page}', [PaymentFormController::class, 'destroy'])->middleware('permission:payment_forms.eliminar');
    Route::get('/tables', [PaymentFormController::class, 'tables']);
    Route::get('/record/{id}', [PaymentFormController::class, 'record']);
});
