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
        Schema::create('payment_reports_movements_estatus', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('description', 50);
            $table->char('action_description', 50);
            $table->char('style', 100)->nullable();
            $table->boolean('finish_estatus')->default(false)->comment('Si true no permitira modificar el movimiento');
            $table->boolean('motivo')->default(false)->comment('Si requiere motivo');
            $table->boolean('soporte')->default(false)->comment('Si requiere soporte');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('idx_gtui15');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reports_movements_estatus');
    }
};
