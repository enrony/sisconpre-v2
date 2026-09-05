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
        Schema::create('users_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('users_id')->nullable()->index('users_profiles_users_id_foreign');
            $table->unsignedBigInteger('profiles_id')->nullable()->index('users_profiles_profiles_id_foreign');
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_profiles');
    }
};
