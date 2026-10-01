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
     * Auditoría de cada cambio de `prestamos.estatus` (Pagado automático,
     * Anulado / Perdido / Reactivado manuales, con su motivo y quién lo hizo).
     */
    public function up(): void
    {
        Schema::create('prestamos_estatus_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prestamo_id')->index();
            $table->unsignedTinyInteger('estatus_anterior');
            $table->unsignedTinyInteger('estatus_nuevo');
            $table->string('motivo', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('prestamo_id')->references('id')->on('prestamos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Permission::findOrCreate('prestamos.cambiar-estado', 'web');

        // Préstamos que ya tienen todas sus cuotas pagadas pero quedaron en
        // "Pendiente" porque nada los cerraba: pasan a "Pagado".
        $saldados = DB::table('prestamos as p')
            ->where('p.estatus', Prestamos::ESTATUS_PENDIENTE)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('prestamos_dias as d')->whereColumn('d.prestamo_id', 'p.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('prestamos_dias as d')
                ->whereColumn('d.prestamo_id', 'p.id')->where('d.apply', true)->where('d.pagado', false))
            ->pluck('p.id');

        DB::table('prestamos')->whereIn('id', $saldados)->update(['estatus' => Prestamos::ESTATUS_PAGADO, 'pagado' => true]);

        DB::table('prestamos_estatus_historial')->insert($saldados->map(fn ($id) => [
            'prestamo_id' => $id,
            'estatus_anterior' => Prestamos::ESTATUS_PENDIENTE,
            'estatus_nuevo' => Prestamos::ESTATUS_PAGADO,
            'motivo' => 'Todas las cuotas pagadas (corrección al instalar el cierre automático)',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }

    public function down(): void
    {
        Permission::where('name', 'prestamos.cambiar-estado')->delete();
        Schema::dropIfExists('prestamos_estatus_historial');
    }
};
