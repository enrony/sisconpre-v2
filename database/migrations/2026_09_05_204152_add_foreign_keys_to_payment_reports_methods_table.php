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
        Schema::table('payment_reports_methods', function (Blueprint $table) {
            $table->foreign(['payment_report_id'], 'fkpri_22')->references(['id'])->on('payment_reports');
            $table->foreign(['bank_id'])->references(['id'])->on('banks');
            $table->foreign(['franquicia_id'])->references(['id'])->on('franquicias');
            $table->foreign(['payment_method_id'])->references(['id'])->on('payment_methods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reports_methods', function (Blueprint $table) {
            $table->dropForeign('fkpri_22');
            $table->dropForeign('payment_reports_methods_bank_id_foreign');
            $table->dropForeign('payment_reports_methods_franquicia_id_foreign');
            $table->dropForeign('payment_reports_methods_payment_method_id_foreign');
        });
    }
};
