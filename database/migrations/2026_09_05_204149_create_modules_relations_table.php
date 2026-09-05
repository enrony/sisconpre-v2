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
        Schema::create('modules_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('modules_father_id')->nullable()->index('modules_relations_modules_father_id_foreign');
            $table->unsignedBigInteger('modules_son_id')->nullable()->index('modules_relations_modules_son_id_foreign');
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules_relations');
    }
};
