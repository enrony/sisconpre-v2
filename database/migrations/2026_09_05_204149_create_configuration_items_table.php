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
        Schema::create('configuration_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 4);
            $table->string('descripcion');
            $table->boolean('numerico')->default(false);
            $table->double('valor_numerico')->default(0);
            $table->string('valor_string', 100)->nullable();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_items');
    }
};
