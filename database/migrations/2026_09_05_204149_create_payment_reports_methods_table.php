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
        Schema::create('payment_reports_methods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_report_id')->index('fkpri_22');
            $table->unsignedBigInteger('payment_method_id')->nullable()->index('payment_reports_methods_payment_method_id_foreign');
            $table->unsignedBigInteger('bank_id')->nullable()->index('payment_reports_methods_bank_id_foreign');
            $table->unsignedBigInteger('franquicia_id')->nullable()->index('payment_reports_methods_franquicia_id_foreign');
            $table->char('referencia', 100)->nullable();
            $table->decimal('importe', 16, 3);
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reports_methods');
    }
};
