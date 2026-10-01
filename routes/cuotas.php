<?php

use App\Http\Controllers\CuotasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cuotas pendientes (por cobrar / por pagar)
|--------------------------------------------------------------------------
|
| Cargado por `bootstrap/app.php` bajo `permission:cuotas.listar`. Qué cuotas
| ve cada uno lo decide `AlcanceCartera` (grupo para el prestamista, las
| propias para el cliente).
|
*/

Route::prefix('/cuotas')->group(function () {
    Route::get('/', [CuotasController::class, 'index'])->name('cuotas');
    Route::get('/records', [CuotasController::class, 'records']);
});
