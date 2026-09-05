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
        Schema::create('clientes_grupos_trabajos_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('clientes_id')->nullable()->index('clientes_grupos_trabajos_users_clientes_id_foreign');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('clientes_grupos_trabajos_users_grupos_trabajos_user_id_foreign');
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes_grupos_trabajos_users');
    }
};
