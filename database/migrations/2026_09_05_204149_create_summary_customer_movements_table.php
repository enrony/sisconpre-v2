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
        Schema::create('summary_customer_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id')->index('summary_customer_movements_cliente_id_foreign');
            $table->decimal('balance', 16, 3);
            $table->decimal('debit', 16, 3)->default(0);
            $table->decimal('credit', 16, 3)->default(0);
            $table->string('notes', 1000)->nullable();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('idx_gtui18');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('summary_customer_movements');
    }
};
