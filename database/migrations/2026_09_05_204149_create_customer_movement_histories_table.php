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
        Schema::create('customer_movement_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id')->index('customer_movement_histories_cliente_id_foreign');
            $table->unsignedTinyInteger('type_movement_id')->index('customer_movement_histories_type_movement_id_foreign');
            $table->unsignedBigInteger('payment_report_id')->nullable()->index('customer_movement_histories_payment_report_id_foreign');
            $table->decimal('amount', 16, 3);
            $table->dateTime('date_movement');
            $table->string('notes', 1000)->nullable();
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('idx_gtui17');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_movement_histories');
    }
};
