<?php

use App\Models\Prestamos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Aprobación del préstamo: eje distinto de `prestamos.estatus` (que
     * describe el avance del cobro: Pendiente/Pagado/Anulado/Perdido, ya en
     * uso por Dashboard/reportes). Un préstamo nuevo nace "Pendiente de
     * aprobación" y no puede recibir un pago informado hasta que se apruebe
     * (`PaymentReportService::verifiedPrestamosAprobados()`).
     */
    public function up(): void
    {
        Schema::create('prestamos_aprobacion_estatus', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->char('description', 50);
            $table->json('type_tag')->nullable();
            $table->timestamps();
        });

        DB::table('prestamos_aprobacion_estatus')->insert([
            ['id' => Prestamos::APROBACION_PENDIENTE, 'description' => 'Pendiente de aprobación', 'type_tag' => json_encode(['type' => 'info']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => Prestamos::APROBACION_APROBADO, 'description' => 'Aprobado', 'type_tag' => json_encode(['type' => 'success']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => Prestamos::APROBACION_RECHAZADO, 'description' => 'Rechazado', 'type_tag' => json_encode(['type' => 'danger']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('prestamos', function (Blueprint $table) {
            $table->unsignedTinyInteger('aprobacion_estatus_id')->default(Prestamos::APROBACION_PENDIENTE)->after('estatus');
            $table->foreign('aprobacion_estatus_id')->references('id')->on('prestamos_aprobacion_estatus');
        });

        // Los préstamos ya existentes están operando: no se retiene ninguno
        // a la espera de una aprobación retroactiva.
        DB::table('prestamos')->update(['aprobacion_estatus_id' => Prestamos::APROBACION_APROBADO]);

        Permission::findOrCreate('prestamos.aprobar', 'web');
    }

    public function down(): void
    {
        Permission::where('name', 'prestamos.aprobar')->delete();

        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropForeign(['aprobacion_estatus_id']);
            $table->dropColumn('aprobacion_estatus_id');
        });

        Schema::dropIfExists('prestamos_aprobacion_estatus');
    }
};
