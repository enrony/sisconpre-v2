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
        Schema::table('customer_movement_histories', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes');
            $table->foreign(['payment_report_id'])->references(['id'])->on('payment_reports');
            $table->foreign(['type_movement_id'])->references(['id'])->on('types_movements');
            $table->foreign(['grupos_trabajos_user_id'], 'fk_gtui17')->references(['id'])->on('grupos_trabajos_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_movement_histories', function (Blueprint $table) {
            $table->dropForeign('customer_movement_histories_cliente_id_foreign');
            $table->dropForeign('customer_movement_histories_payment_report_id_foreign');
            $table->dropForeign('customer_movement_histories_type_movement_id_foreign');
            $table->dropForeign('fk_gtui17');
        });
    }
};
