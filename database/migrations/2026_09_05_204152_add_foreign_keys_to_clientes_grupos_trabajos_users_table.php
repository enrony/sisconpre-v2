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
        Schema::table('clientes_grupos_trabajos_users', function (Blueprint $table) {
            $table->foreign(['clientes_id'])->references(['id'])->on('clientes')->onDelete('cascade');
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes_grupos_trabajos_users', function (Blueprint $table) {
            $table->dropForeign('clientes_grupos_trabajos_users_clientes_id_foreign');
            $table->dropForeign('clientes_grupos_trabajos_users_grupos_trabajos_user_id_foreign');
        });
    }
};
