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
        Schema::create('countries', function (Blueprint $table) {
            $table->char('id', 3)->primary();
            $table->char('Name', 52);
            $table->enum('Continent', ['Asia', 'Europe', 'North America', 'Africa', 'Oceania', 'Antarctica', 'South America']);
            $table->char('Region', 26);
            $table->double('SurfaceArea');
            $table->smallInteger('IndepYear')->nullable();
            $table->integer('Population');
            $table->double('LifeExpectancy')->nullable();
            $table->double('GNP');
            $table->double('GNPOld')->nullable();
            $table->char('LocalName', 45);
            $table->char('GovernmentForm', 45);
            $table->char('HeadOfState', 60);
            $table->integer('Capital')->nullable();
            $table->char('Code2', 2);
            $table->timestamps();
            $table->unsignedTinyInteger('estatus')->default(0)->index();
            $table->string('timezone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
