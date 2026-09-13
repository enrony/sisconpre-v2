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

// Fuera del prefix()->group() de abajo a propósito: ver el comentario equivalente
// en routes/PaymentReport.php (Route::prefix()+post('/') produce una URL con slash
// final que el .htaccess redirige 301, degradando el POST a GET).
Route::middleware(['auth', 'verified'])->post('/PaymentReportsMovementsEstatu', [PaymentReportsMovementsEstatuController::class, 'store'])
    ->middleware('permission:payment_report.registrar|payment_report.editar');

Route::prefix('/PaymentReportsMovementsEstatu')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/obtenerReportPaymentActivos/{cliente}', [PaymentReportsMovementsEstatuController::class, 'obtenerReportPaymentActivos']);
    // Route::delete('/{id}/{page}', [PaymentReportController::class, 'destroy']);
    Route::get('/tables', [PaymentReportsMovementsEstatuController::class, 'tables']);
    // Route::get('/record/{id}', [PaymentReportController::class, 'record']);
});
