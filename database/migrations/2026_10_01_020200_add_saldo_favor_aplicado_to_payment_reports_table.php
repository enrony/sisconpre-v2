<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parte del pago de cuotas cubierta con el saldo a favor del cliente. No
     * es un método de pago (no es dinero que entra): por eso no va en
     * `payment_reports_methods` y no suma a "monto recibido" en reportes.
     */
    public function up(): void
    {
        Schema::table('payment_reports', function (Blueprint $table) {
            $table->decimal('saldo_favor_aplicado', 16, 3)->default(0)->after('importe');
        });
    }

    public function down(): void
    {
        Schema::table('payment_reports', function (Blueprint $table) {
            $table->dropColumn('saldo_favor_aplicado');
        });
    }
};
