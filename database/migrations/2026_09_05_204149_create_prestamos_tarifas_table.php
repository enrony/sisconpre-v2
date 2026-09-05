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
        Schema::create('prestamos_tarifas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('tarifa', 4);
            $table->unsignedBigInteger('prestamo_id')->index('prestamos_tarifas_prestamo_id_foreign');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('prestamos_tarifas_grupos_trabajos_user_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos_tarifas');
    }
};
