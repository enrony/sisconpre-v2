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
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreign(['city_id'])->references(['id'])->on('cities');
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['idtipo_documento'])->references(['id'])->on('tipos_documentos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign('clientes_city_id_foreign');
            $table->dropForeign('clientes_grupos_trabajos_user_id_foreign');
            $table->dropForeign('clientes_idtipo_documento_foreign');
        });
    }
};
