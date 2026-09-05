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
        Schema::create('tipos_documentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 200);
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('tipos_documentos_grupos_trabajos_user_id_foreign');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->string('sigla', 3);
            $table->char('country_id', 3)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_documentos');
    }
};
