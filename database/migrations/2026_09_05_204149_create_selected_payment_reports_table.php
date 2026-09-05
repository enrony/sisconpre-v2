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
        Schema::create('selected_payment_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('prestamos_dia_id')->index('fkpd_08');
            $table->unsignedBigInteger('payment_report_id')->index('fkpr_13');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->boolean('error_process')->default(false)->comment('True si falla al aplicar el pago de la cuota por esta estar previamente pagada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selected_payment_reports');
    }
};
