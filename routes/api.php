<?php

use App\Models\tiposDocumentos;
use Illuminate\Support\Facades\Route;

// Utilidades usadas por el front (portadas del sistema legado).
Route::get('/listaTipoDocu/{cod_pais?}', fn () => tiposDocumentos::all());
Route::get('/dateServerCurrent', fn () => response()->json(['date' => now()->toDateString()]));
