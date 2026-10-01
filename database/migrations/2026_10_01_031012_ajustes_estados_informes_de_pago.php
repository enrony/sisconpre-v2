<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RECHAZADO = 3;

    public function up(): void
    {
        // Rechazar un pago exige adjuntar el soporte (además del motivo, que ya se pedía).
        DB::table('payment_reports_movements_estatus')->where('id', self::RECHAZADO)->update(['soporte' => true]);

        // Los informes informados con dinero nacían sin estado (se guardaba solo el
        // movimiento): no los contaba el KPI "Informes por revisar" ni el filtro por
        // estado. Se completa con el estado del último movimiento de cada uno.
        DB::statement(<<<'SQL'
            UPDATE payment_reports pr
            SET pr.payment_reports_movements_estatus_id = (
                SELECT m.estatus FROM payment_reports_movements m
                WHERE m.payment_report_id = pr.id
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT 1
            )
            WHERE pr.payment_reports_movements_estatus_id IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::table('payment_reports_movements_estatus')->where('id', self::RECHAZADO)->update(['soporte' => false]);
    }
};
