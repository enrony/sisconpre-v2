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
        Schema::table('selected_payment_reports', function (Blueprint $table) {
            $table->foreign(['prestamos_dia_id'], 'fkpd_08')->references(['id'])->on('prestamos_dias');
            $table->foreign(['payment_report_id'], 'fkpr_13')->references(['id'])->on('payment_reports');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_payment_reports', function (Blueprint $table) {
            $table->dropForeign('fkpd_08');
            $table->dropForeign('fkpr_13');
        });
    }
};
