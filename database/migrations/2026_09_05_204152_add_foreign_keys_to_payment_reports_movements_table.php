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
        Schema::table('payment_reports_movements', function (Blueprint $table) {
            $table->foreign(['estatus'], 'fk15_status')->references(['id'])->on('payment_reports_movements_estatus');
            $table->foreign(['grupos_trabajos_user_id'], 'fkgtui_11')->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['payment_report_id'], 'fkpri_11')->references(['id'])->on('payment_reports');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reports_movements', function (Blueprint $table) {
            $table->dropForeign('fk15_status');
            $table->dropForeign('fkgtui_11');
            $table->dropForeign('fkpri_11');
        });
    }
};
