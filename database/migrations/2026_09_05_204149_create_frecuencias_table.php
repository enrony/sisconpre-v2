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
        Schema::create('frecuencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre');
            $table->unsignedTinyInteger('cantidad')->default(1);
            $table->boolean('por_defecto')->default(false);
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('frecuencias_grupos_trabajos_user_id_foreign');
            $table->timestamps();
            $table->unsignedBigInteger('tipo_frecuencia_prestamo_id')->nullable()->index('frecuencias_tipo_frecuencia_prestamo_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frecuencias');
    }
};
