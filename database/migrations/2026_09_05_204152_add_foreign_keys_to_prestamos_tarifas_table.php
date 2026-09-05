<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prestamos_tarifas', function (Blueprint $table) {
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['prestamo_id'])->references(['id'])->on('prestamos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos_tarifas', function (Blueprint $table) {
            $table->dropForeign('prestamos_tarifas_grupos_trabajos_user_id_foreign');
            $table->dropForeign('prestamos_tarifas_prestamo_id_foreign');
        });
    }
};
