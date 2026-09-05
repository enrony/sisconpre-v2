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
        Schema::table('prestamos_estatus', function (Blueprint $table) {
            $table->foreign(['grupos_trabajos_user_id'], 'fk_gtuipe1')->references(['id'])->on('grupos_trabajos_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos_estatus', function (Blueprint $table) {
            $table->dropForeign('fk_gtuipe1');
        });
    }
};
