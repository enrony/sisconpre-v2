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
        Schema::table('tipo_prestamos', function (Blueprint $table) {
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['tipo_frecuencia_prestamo_id'])->references(['id'])->on('tipo_frecuencia_prestamos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_prestamos', function (Blueprint $table) {
            $table->dropForeign('tipo_prestamos_grupos_trabajos_user_id_foreign');
            $table->dropForeign('tipo_prestamos_tipo_frecuencia_prestamo_id_foreign');
        });
    }
};
