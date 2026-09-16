<?php

use App\Http\Controllers\ReportePrestamosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Reporte de préstamos (solo lectura)
|--------------------------------------------------------------------------
|
| Cargado por `bootstrap/app.php` bajo `permission:reporte_prestamos.listar`.
|
*/

Route::middleware(['auth', 'verified'])->prefix('/reporte_prestamos')->group(function () {
    Route::get('/', [ReportePrestamosController::class, 'index'])->name('reporte_prestamos');
    Route::get('/records', [ReportePrestamosController::class, 'records']);
    Route::get('/exportar-excel', [ReportePrestamosController::class, 'exportarExcel']);
    Route::get('/exportar-pdf', [ReportePrestamosController::class, 'exportarPdf']);
});
