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
        Schema::create('modules_actions_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profiles_id')->nullable()->index('fk01_profileid');
            $table->unsignedBigInteger('modules_actions_id')->nullable()->index('fk01_moactions');
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules_actions_profiles');
    }
};
