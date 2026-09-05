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
        Schema::table('support_payment_reports', function (Blueprint $table) {
            $table->foreign(['payment_report_id'], 'fkpr_11')->references(['id'])->on('payment_reports');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_payment_reports', function (Blueprint $table) {
            $table->dropForeign('fkpr_11');
        });
    }
};
