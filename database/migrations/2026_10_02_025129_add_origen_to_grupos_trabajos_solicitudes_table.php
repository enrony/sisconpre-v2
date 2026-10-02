<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * De dónde salió la solicitud: `registro` (usuario nuevo que se registró
     * con el código del grupo) o `codigo` (usuario existente que pidió unirse
     * a otro grupo). Ambas las aprueba el dueño del grupo.
     */
    public function up(): void
    {
        Schema::table('grupos_trabajos_solicitudes', function (Blueprint $table) {
            $table->string('origen', 12)->default('codigo')->after('estatus');
        });
    }

    public function down(): void
    {
        Schema::table('grupos_trabajos_solicitudes', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
