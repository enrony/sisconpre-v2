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
        Schema::create('payment_reports_support_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_reports_movement_id')->index('fkprsm_01');
            $table->char('soporte', 200)->nullable()->comment('Indicar ruta de archivo');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reports_support_movements');
    }
};
