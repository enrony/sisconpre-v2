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
        Schema::create('cities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('nombre', 35);
            $table->char('country_id', 3)->index();
            $table->char('District', 20)->nullable();
            $table->integer('Population')->nullable();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index();
            $table->timestamps();
            $table->unsignedBigInteger('department_id')->nullable()->index('cities_department_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
