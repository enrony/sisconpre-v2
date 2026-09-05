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
        Schema::create('modules_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('modules_id')->nullable()->index('modules_actions_modules_id_foreign');
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->unsignedTinyInteger('action_id')->nullable()->index('modules_actions_action_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules_actions');
    }
};
