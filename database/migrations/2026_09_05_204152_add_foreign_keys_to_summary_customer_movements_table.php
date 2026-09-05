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
        Schema::table('summary_customer_movements', function (Blueprint $table) {
            $table->foreign(['grupos_trabajos_user_id'], 'fk_gtui18')->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('summary_customer_movements', function (Blueprint $table) {
            $table->dropForeign('fk_gtui18');
            $table->dropForeign('summary_customer_movements_cliente_id_foreign');
        });
    }
};
