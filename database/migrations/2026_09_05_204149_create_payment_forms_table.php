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
        Schema::create('payment_forms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 3);
            $table->string('description', 50);
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_forms');
    }
};
