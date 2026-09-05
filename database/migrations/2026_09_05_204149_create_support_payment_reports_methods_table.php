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
        Schema::create('support_payment_reports_methods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_reports_method_id')->index('fkprmi_05');
            $table->char('support', 200)->nullable()->comment('nombre como se guarda el archivo');
            $table->char('name', 200)->nullable()->comment('nombre a mostra del archivo');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_payment_reports_methods');
    }
};
