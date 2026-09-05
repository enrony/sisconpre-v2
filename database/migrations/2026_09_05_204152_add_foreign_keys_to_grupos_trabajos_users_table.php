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
        Schema::table('grupos_trabajos_users', function (Blueprint $table) {
            $table->foreign(['idgrupo_trabajo'])->references(['id'])->on('grupos_trabajos');
            $table->foreign(['iduser'])->references(['id'])->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grupos_trabajos_users', function (Blueprint $table) {
            $table->dropForeign('grupos_trabajos_users_idgrupo_trabajo_foreign');
            $table->dropForeign('grupos_trabajos_users_iduser_foreign');
        });
    }
};
