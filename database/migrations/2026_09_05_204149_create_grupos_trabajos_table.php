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
        Schema::create('grupos_trabajos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 200);
            $table->string('code', 10)->index();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('city_id')->nullable()->index('grupos_trabajos_city_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_trabajos');
    }
};
