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
        Schema::table('payment_reports', function (Blueprint $table) {
            $table->foreign(['payment_reports_movements_estatus_id'], 'fk_payment_reports_movements_estatus_id')->references(['id'])->on('payment_reports_movements_estatus')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['grupos_trabajos_user_id'], 'fkgtui_12')->references(['id'])->on('grupos_trabajos_users');
            $table->foreign(['payment_report_id'], 'fkpri_23')->references(['id'])->on('payment_reports');
            $table->foreign(['type_payment_record_id'], 'fktpri_pr01')->references(['id'])->on('type_payment_records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reports', function (Blueprint $table) {
            $table->dropForeign('fk_payment_reports_movements_estatus_id');
            $table->dropForeign('fkgtui_12');
            $table->dropForeign('fkpri_23');
            $table->dropForeign('fktpri_pr01');
        });
    }
};
