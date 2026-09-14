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
        Schema::table('user_countries', function (Blueprint $table) {
            // Mismo patrón que `current_grupo` en grupos_trabajos_users: el país
            // que el usuario tiene activo en la sesión actual.
            $table->unsignedTinyInteger('current_country')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_countries', function (Blueprint $table) {
            $table->dropColumn('current_country');
        });
    }
};
