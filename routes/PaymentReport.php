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

// Fuera del prefix()->group() de abajo a propósito: Route::prefix('/payment_report')
// + Route::post('/') resuelve a '/payment_report/' (con slash), y el .htaccess de
// Laravel redirige 301 cualquier URL con slash final a la versión sin slash — un
// 301 sobre un POST se reenvía como GET, perdiendo el body. No mover de vuelta al grupo.
Route::middleware(['auth', 'verified'])->post('/payment_report', [PaymentReportController::class, 'store'])
    ->middleware('permission:payment_report.registrar|payment_report.gestionar-informe-de-pago');

Route::prefix('/payment_report')->middleware(['auth', 'verified'])->group(function () {
    Route::post('/change_estatus_report', [PaymentReportController::class, 'change_estatus_report'])
        ->middleware('permission:payment_report.gestionar-informe-de-pago|payment_report.editar');
    Route::get('/obtenerReportPaymentActivos/{cliente}', [PaymentReportController::class, 'obtenerReportPaymentActivos']);
    // Route::delete('/{id}/{page}', [PaymentReportController::class, 'destroy']);
    // Route::get('/tables', [PaymentReportController::class, 'tables']);
    Route::get('/record/{id}', [PaymentReportController::class, 'record']);
    Route::get('/records', [PaymentReportController::class, 'records']);
});
