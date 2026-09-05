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
        Schema::create('bank_account_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 2);
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
        Schema::dropIfExists('bank_account_types');
    }
};
