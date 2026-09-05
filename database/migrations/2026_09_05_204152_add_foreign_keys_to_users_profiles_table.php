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
        Schema::table('users_profiles', function (Blueprint $table) {
            $table->foreign(['profiles_id'])->references(['id'])->on('profiles')->onDelete('cascade');
            $table->foreign(['users_id'])->references(['id'])->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_profiles', function (Blueprint $table) {
            $table->dropForeign('users_profiles_profiles_id_foreign');
            $table->dropForeign('users_profiles_users_id_foreign');
        });
    }
};
