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
        Schema::table('cities', function (Blueprint $table) {
            $table->foreign(['country_id'])->references(['id'])->on('countries')->onDelete('cascade');
            $table->foreign(['department_id'])->references(['id'])->on('departments')->onDelete('cascade');
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign('cities_country_id_foreign');
            $table->dropForeign('cities_department_id_foreign');
            $table->dropForeign('cities_grupos_trabajos_user_id_foreign');
        });
    }
};
