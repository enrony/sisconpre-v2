<?php

use App\Http\Controllers\PaymentMethodController;
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

Route::middleware(['auth', 'verified'])->get('/payment_methods', [PaymentMethodController::class, 'index'])->name('payment_methods');

Route::prefix('/payment_methods')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [PaymentMethodController::class, 'store']);
    Route::delete('/{id}/{page}', [PaymentMethodController::class, 'destroy']);
    Route::get('/record/{id}', [PaymentMethodController::class, 'record']);
    // `/payment_methods/tables` vive en routes/shared.php (transversal, solo auth).
});
