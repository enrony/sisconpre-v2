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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cliente_id')->default(1)->index('prestamos_id_cliente_index');
            $table->unsignedBigInteger('grupos_trabajos_user_id')->default(1)->index();
            $table->decimal('monto_prestamo', 15);
            $table->decimal('cuota', 15);
            $table->boolean('pagado')->default(false);
            $table->boolean('anulado')->default(false);
            $table->boolean('perdido')->default(false);
            $table->unsignedTinyInteger('estatus')->default(1)->index();
            $table->timestamps();
            $table->json('cliente')->nullable();
            $table->decimal('tasa', 4)->default(0);
            $table->json('form')->nullable();
            $table->boolean('incluir_festivos')->default(false);
            $table->boolean('incluir_domingos')->default(false);
            $table->boolean('cuota_sugerida')->default(false);
            $table->decimal('valor_cuota_sugerida', 15)->default(0);
            $table->decimal('utilidad', 15)->default(0);
            $table->decimal('resta', 15)->default(0);
            $table->decimal('total', 15)->default(0);
            $table->decimal('cuota_establecida', 15)->default(0);
            $table->date('date_first_pay');
            $table->date('date_last_pay');
            $table->unsignedBigInteger('tipo_prestamo_id')->index('prestamos_tipo_prestamo_id_foreign');
            $table->char('country_id', 3)->index();
            $table->boolean('apply_surcharge')->default(false)->comment('Si true entonces se aplicara recargo al prestamo');
            $table->boolean('incluir_festivos_surcharge')->default(false)->comment('Si true toma en cuenta los festivos para aplicar recargos');
            $table->boolean('incluir_domingos_surcharge')->default(false)->comment('Si true toma en cuenta los domingos para aplicar recargos');
            $table->unsignedTinyInteger('pause_surcharge')->default(0)->comment('Indica si se pausa la aplicación del recargo (0: No, 1: Sí)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
