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
        Schema::table('tipos_documentos', function (Blueprint $table) {
            $table->foreign(['country_id'])->references(['id'])->on('countries')->onDelete('cascade');
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipos_documentos', function (Blueprint $table) {
            $table->dropForeign('tipos_documentos_country_id_foreign');
            $table->dropForeign('tipos_documentos_grupos_trabajos_user_id_foreign');
        });
    }
};
