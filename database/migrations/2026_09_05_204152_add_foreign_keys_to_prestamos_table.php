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
        Schema::table('prestamos', function (Blueprint $table) {
            $table->foreign(['estatus'], 'fkpr1_status')->references(['id'])->on('prestamos_estatus');
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes');
            $table->foreign(['country_id'])->references(['id'])->on('countries')->onDelete('cascade');
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['tipo_prestamo_id'])->references(['id'])->on('tipo_prestamos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign('fkpr1_status');
            $table->dropForeign('prestamos_cliente_id_foreign');
            $table->dropForeign('prestamos_country_id_foreign');
            $table->dropForeign('prestamos_grupos_trabajos_user_id_foreign');
            $table->dropForeign('prestamos_tipo_prestamo_id_foreign');
        });
    }
};
