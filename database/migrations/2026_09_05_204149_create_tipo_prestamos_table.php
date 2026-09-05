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
        Schema::create('tipo_prestamos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('descripcion', 50);
            $table->boolean('active')->default(true);
            $table->boolean('por_defecto')->default(false);
            $table->unsignedTinyInteger('cantidad');
            $table->unsignedBigInteger('tipo_frecuencia_prestamo_id')->nullable()->index('tipo_prestamos_tipo_frecuencia_prestamo_id_foreign');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('tipo_prestamos_grupos_trabajos_user_id_foreign');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_prestamos');
    }
};
