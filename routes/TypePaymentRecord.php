<?php

use App\Http\Controllers\TypePaymentRecordController;
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

Route::middleware(['auth', 'verified'])->get('/type_payment_record', [TypePaymentRecordController::class, 'index'])->name('type_payment_record');

Route::prefix('/type_payment_record')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [TypePaymentRecordController::class, 'store']);
    Route::delete('/{id}/{page}', [TypePaymentRecordController::class, 'destroy']);
    Route::get('/tables', [TypePaymentRecordController::class, 'tables']);
    Route::get('/record/{id}', [TypePaymentRecordController::class, 'record']);
});
