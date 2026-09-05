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
        Schema::create('grupos_trabajos_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('idgrupo_trabajo')->index();
            $table->unsignedBigInteger('iduser')->index();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->unsignedTinyInteger('current_grupo')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos_trabajos_users');
    }
};
