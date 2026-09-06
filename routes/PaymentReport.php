<?php

use App\Http\Controllers\PaymentReportController;
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

Route::middleware(['auth', 'verified'])->get('/payment_report', [PaymentReportController::class, 'index'])->name('payment_report');

Route::prefix('/payment_report')->middleware(['auth', 'verified'])->group(function () {
    Route::post('/', [PaymentReportController::class, 'store'])
        ->middleware('permission:payment_report.registrar|payment_report.gestionar-informe-de-pago');
    Route::post('/change_estatus_report', [PaymentReportController::class, 'change_estatus_report'])
        ->middleware('permission:payment_report.gestionar-informe-de-pago|payment_report.editar');
    Route::get('/obtenerReportPaymentActivos/{cliente}', [PaymentReportController::class, 'obtenerReportPaymentActivos']);
    // Route::delete('/{id}/{page}', [PaymentReportController::class, 'destroy']);
    // Route::get('/tables', [PaymentReportController::class, 'tables']);
    Route::get('/record/{id}', [PaymentReportController::class, 'record']);
    Route::get('/records', [PaymentReportController::class, 'records']);
});
