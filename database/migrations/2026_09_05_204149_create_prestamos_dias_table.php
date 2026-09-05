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
        Schema::create('prestamos_dias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('prestamo_id')->default(1)->index('prestamos_dias_id_prestamo_index');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->default(1)->index();
            $table->decimal('cuota', 15);
            $table->boolean('pagado')->default(false);
            $table->boolean('demorado')->default(false);
            $table->date('fecha_pago')->nullable();
            $table->dateTime('fecha_pagado')->nullable();
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->date('date');
            $table->boolean('apply')->default(false);
            $table->boolean('dom')->default(false);
            $table->boolean('festivo')->default(false);
            $table->char('sigla', 6)->default('0');
            $table->date('date_before')->nullable();
            $table->boolean('date_change')->default(false);
            $table->boolean('is_surcharge')->default(false)->comment('Si no seporta pago en la fecha mas dias para aplicar recargo se crea un nuevo registro con este campo en true ');
            $table->boolean('apply_surcharge')->default(true)->comment('Si true entonces se aplicara recargo');
            $table->boolean('surcharge_applied')->default(true)->comment('Si true entonces le fue aplicado el recargo');
            $table->unsignedTinyInteger('surcharge')->default(0)->comment('Procentaje a aplicar de recargo');
            $table->unsignedTinyInteger('days_apply_surcharge')->default(0)->comment('Dias contados para aplicar recargo, esto generar un nuevo registro en esta tabla');
            $table->date('day_apply_surcharge')->nullable()->comment('Dia en la cual se aplica el recargo');
            $table->boolean('notified_surcharge')->default(false)->comment('Si true indica que fue notificado al cliente vía email');
            $table->dateTime('date_surcharge_notified')->nullable()->comment('Fecha envio notificacion');
            $table->unsignedTinyInteger('error_sending_notification')->default(0);
            $table->unsignedBigInteger('prestamo_dia_id')->nullable()->index('prestamos_dias_prestamo_dia_id_foreign')->comment('Id de la cuota a a la cual se le aplicara el recargo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos_dias');
    }
};
