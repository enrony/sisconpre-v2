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
        Schema::create('tipo_frecuencia_prestamos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('descripcion', 50);
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_frecuencia_prestamos');
    }
};
