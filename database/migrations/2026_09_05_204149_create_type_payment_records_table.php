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
        Schema::create('type_payment_records', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('code', 3);
            $table->char('description', 100);
            $table->boolean('require_approval')->default(false)->comment('Requiere aprobación');
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
        Schema::dropIfExists('type_payment_records');
    }
};
