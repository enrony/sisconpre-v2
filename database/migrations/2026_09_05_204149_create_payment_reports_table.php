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
        Schema::create('payment_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id');
            $table->json('cliente');
            $table->unsignedTinyInteger('destination')->default(1)->comment('1: cuotas, 2: saldo a favor');
            $table->unsignedTinyInteger('type_payment_record_id')->nullable()->index('fktpri_pr01');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->nullable()->index('idxgtuipr_12');
            $table->boolean('approved')->default(false);
            $table->unsignedBigInteger('payment_report_id')->nullable()->index('fkpri_23')->comment('Se genera si existe novedad con el pago inicial, por ajuste en valor por pago no confirmado');
            $table->string('motivo')->nullable()->comment('En caso de novedad indicar motivo');
            $table->decimal('importe', 16, 3)->default(0)->comment('Valor que se agregará a saldo a favor, cartera');
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->unsignedTinyInteger('payment_reports_movements_estatus_id')->nullable()->index('fk_payment_reports_movements_estatus_id')->comment('Estatus del último movimiento del informe de pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reports');
    }
};
