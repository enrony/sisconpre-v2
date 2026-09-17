<?php

use App\Http\Controllers\ReporteInformesPagoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Reporte de informes de pago (solo lectura)
|--------------------------------------------------------------------------
|
| Cargado por `bootstrap/app.php` bajo `permission:reporte_informes_pago.listar`.
|
*/

Route::middleware(['auth', 'verified'])->prefix('/reporte_informes_pago')->group(function () {
    Route::get('/', [ReporteInformesPagoController::class, 'index'])->name('reporte_informes_pago');
    Route::get('/records', [ReporteInformesPagoController::class, 'records']);
    Route::get('/resumen', [ReporteInformesPagoController::class, 'resumen']);
    Route::get('/exportar-excel', [ReporteInformesPagoController::class, 'exportarExcel']);
    Route::get('/exportar-pdf', [ReporteInformesPagoController::class, 'exportarPdf']);
});
