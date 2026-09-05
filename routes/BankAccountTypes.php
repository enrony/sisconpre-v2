<?php

use App\Http\Controllers\BankAccountTypeController;
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

Route::middleware(['auth', 'verified'])->get('/bank_account_types', [BankAccountTypeController::class, 'index'])->name('bank_account_types');

Route::prefix('/bank_account_types')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [BankAccountTypeController::class, 'store']);
    Route::delete('/{id}/{page}', [BankAccountTypeController::class, 'destroy']);
    Route::get('/tables', [BankAccountTypeController::class, 'tables']);
    Route::get('/record/{id}', [BankAccountTypeController::class, 'record']);
});
