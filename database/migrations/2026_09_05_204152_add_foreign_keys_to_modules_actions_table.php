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
        Schema::table('modules_actions', function (Blueprint $table) {
            $table->foreign(['action_id'])->references(['id'])->on('actions');
            $table->foreign(['modules_id'])->references(['id'])->on('modules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules_actions', function (Blueprint $table) {
            $table->dropForeign('modules_actions_action_id_foreign');
            $table->dropForeign('modules_actions_modules_id_foreign');
        });
    }
};
