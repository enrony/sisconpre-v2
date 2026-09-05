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
        Schema::create('types_movements', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('code', 2);
            $table->char('description', 100);
            $table->char('operation', 2)->comment('S: Suma, R: Resta');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('idx_gtui16');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types_movements');
    }
};
