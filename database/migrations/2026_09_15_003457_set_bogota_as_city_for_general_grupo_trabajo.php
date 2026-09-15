<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El grupo de trabajo "General" (al que hoy pertenecen el superusuario y
     * Maria Ruiz) no tenía `city_id` cargado, así que `Controller::obtenerPaisActivo()`
     * no puede resolverle país a nadie que dependa de él. Se le asigna Bogotá,
     * Colombia — coincide con la asignación que Maria Ruiz ya tenía antes por
     * `user_countries` (decisión confirmada con el usuario, no un valor inventado).
     *
     * `whereNull('city_id')` de propósito: si alguien ya le cargó una ciudad a
     * mano antes de que corra esta migración, no la pisa.
     */
    public function up(): void
    {
        $bogota = DB::table('cities')
            ->where('country_id', 'COL')
            ->where('nombre', 'LIKE', '%Bogot%')
            ->orderBy('id')
            ->value('id');

        if ($bogota === null) {
            return;
        }

        DB::table('grupos_trabajos')
            ->where('nombre', 'General')
            ->whereNull('city_id')
            ->update(['city_id' => $bogota]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('grupos_trabajos')
            ->where('nombre', 'General')
            ->update(['city_id' => null]);
    }
};
