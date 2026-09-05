<?php

use App\Http\Controllers\BankController;
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

Route::middleware(['auth', 'verified'])->get('/banks', [BankController::class, 'index'])->name('banks');

Route::prefix('/banks')->middleware(['auth', 'verified'])->group(function () {
    Route::put('/', [BankController::class, 'store']);
    Route::delete('/{id}/{page}', [BankController::class, 'destroy']);
    Route::get('/tables/{country_id?}', [BankController::class, 'tables']);
    Route::get('/record/{id}', [BankController::class, 'record']);
});
