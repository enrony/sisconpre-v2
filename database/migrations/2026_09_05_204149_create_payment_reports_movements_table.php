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
        Schema::create('payment_reports_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_report_id')->index('fkpri_11');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('idxgtui_11');
            $table->string('motivo', 3000)->nullable();
            $table->unsignedTinyInteger('estatus')->default(1)->index()->comment('1: Pendiente, 2: Aprobao, 3: Rechazado, 4: En revisión');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reports_movements');
    }
};
