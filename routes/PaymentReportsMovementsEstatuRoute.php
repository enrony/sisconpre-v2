<?php

use App\Http\Controllers\PaymentReportsMovementsEstatuController;
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

Route::middleware(['auth', 'verified'])->get('/PaymentReportsMovementsEstatu', [PaymentReportsMovementsEstatuController::class, 'index'])->name('payment_report_movement_estatu');

Route::prefix('/PaymentReportsMovementsEstatu')->middleware(['auth', 'verified'])->group(function () {
    Route::post('/', [PaymentReportsMovementsEstatuController::class, 'store']);
    Route::get('/obtenerReportPaymentActivos/{cliente}', [PaymentReportsMovementsEstatuController::class, 'obtenerReportPaymentActivos']);
    // Route::delete('/{id}/{page}', [PaymentReportController::class, 'destroy']);
    Route::get('/tables', [PaymentReportsMovementsEstatuController::class, 'tables']);
    // Route::get('/record/{id}', [PaymentReportController::class, 'record']);
});
