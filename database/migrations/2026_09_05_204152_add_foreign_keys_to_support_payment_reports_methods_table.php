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
        Schema::table('support_payment_reports_methods', function (Blueprint $table) {
            $table->foreign(['payment_reports_method_id'], 'fkprmi_05')->references(['id'])->on('payment_reports_methods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_payment_reports_methods', function (Blueprint $table) {
            $table->dropForeign('fkprmi_05');
        });
    }
};
