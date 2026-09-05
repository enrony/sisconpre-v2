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
        Schema::table('payment_reports_support_movements', function (Blueprint $table) {
            $table->foreign(['payment_reports_movement_id'], 'fkprsm_01')->references(['id'])->on('payment_reports_movements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_reports_support_movements', function (Blueprint $table) {
            $table->dropForeign('fkprsm_01');
        });
    }
};
