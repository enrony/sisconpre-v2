<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\FranquiciaController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PrestamosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoints de datos auxiliares (transversales)
|--------------------------------------------------------------------------
|
| Catálogos para selects y listados JSON de apoyo que consumen VARIOS módulos
| del panel (p. ej. el asistente de préstamos usa `/clientes/tables`, "Informar
| un pago" usa `/banks/tables` + `/payment_methods/tables` + ...). Solo exigen
| estar autenticado — NO se gatean por `permission:<módulo>.listar`, porque un
| usuario con permiso de un módulo no tiene por qué tener el de los otros.
| Ver PLAN_MIGRACION.md §9.
|
*/

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/banks/tables/{country_id?}', [BankController::class, 'tables']);
    Route::get('/franquicias/tables', [FranquiciaController::class, 'tables']);
    Route::get('/payment_methods/tables', [PaymentMethodController::class, 'tables']);

    Route::get('/clientes/tables', [ClientesController::class, 'tables']);
    Route::get('/clientes/lista-clientes-json', [ClientesController::class, 'listaClientesJson']);
    Route::get('/clientes/lista-clientes-json-basic', [ClientesController::class, 'listaClientesJsonBasic']);

    Route::get('/prestamos/obtenerPrestamosActivos/{cliente}', [PrestamosController::class, 'obtenerPrestamosActivos']);
});
