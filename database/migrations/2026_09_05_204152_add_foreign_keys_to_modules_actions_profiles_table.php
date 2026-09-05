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
        Schema::table('modules_actions_profiles', function (Blueprint $table) {
            $table->foreign(['modules_actions_id'], 'fk01_moactions')->references(['id'])->on('modules_actions')->onDelete('cascade');
            $table->foreign(['profiles_id'], 'fk01_profileid')->references(['id'])->on('profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules_actions_profiles', function (Blueprint $table) {
            $table->dropForeign('fk01_moactions');
            $table->dropForeign('fk01_profileid');
        });
    }
};
