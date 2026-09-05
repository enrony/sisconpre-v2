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
        Schema::create('clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 200);
            $table->string('nombre_segundo', 200)->nullable();
            $table->string('apellido', 200)->nullable();
            $table->string('apellido_segundo', 200)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('documento', 30);
            $table->string('telefono', 30);
            $table->string('direccion', 2000);
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->default(1)->index();
            $table->unsignedBigInteger('idtipo_documento')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index('clientes_city_id_foreign');
            $table->timestamp('email_verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
