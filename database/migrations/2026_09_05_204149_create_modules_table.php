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
        Schema::create('modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('clave')->nullable();
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('padre')->default(false);
            $table->boolean('subpadre')->default(false);
            $table->boolean('visible_menu')->default(true);
            $table->boolean('route_url')->default(false);
            $table->string('style')->nullable();
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->unsignedTinyInteger('order')->default(0);
            $table->boolean('method_post')->default(false);
            $table->json('multiple_selection')->nullable();
            $table->json('multiple_selection_actions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
