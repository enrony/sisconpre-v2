<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos maestros portados del sistema legado (dump `prestamos_db.sql`).
 *
 * Cada archivo `database/seeders/data/<tabla>.sql` contiene los `INSERT`
 * originales, sin transformar. Son datos de referencia globales
 * (`grupos_trabajos_user_id` = NULL): países, ciudades, departamentos,
 * bancos, festivos, frecuencias, tipos y catálogos de estatus.
 *
 * Idempotente: vacía cada tabla y la recarga. NO incluye datos de tenant
 * (clientes, préstamos, grupos de trabajo, RBAC) — esos vienen del import
 * de datos y de los seeders de RBAC (ver PLAN_MIGRACION.md §5 y §11).
 */
class LegacyCatalogSeeder extends Seeder
{
    /**
     * Tablas en orden que respeta las FKs entre catálogos.
     *
     * @var list<string>
     */
    private const TABLES = [
        'countries',
        'departments',
        'cities',
        'banks',
        'bank_account_types',
        'country_holidays',
        'tipo_frecuencia_prestamos',
        'frecuencias',
        'tipo_prestamos',
        'monedas',
        'actions',
        'tipos_documentos',
        'types_movements',
        'type_payment_records',
        'payment_forms',
        'payment_methods',
        'prestamos_estatus',
        'payment_reports_movements_estatus',
    ];

    public function run(): void
    {
        $dir = database_path('seeders/data');

        Schema::disableForeignKeyConstraints();

        foreach (array_reverse(self::TABLES) as $table) {
            DB::table($table)->delete();
        }

        foreach (self::TABLES as $table) {
            $file = "{$dir}/{$table}.sql";

            if (! is_file($file)) {
                $this->command?->warn("  [catálogo] falta {$table}.sql — se omite");

                continue;
            }

            DB::unprepared(file_get_contents($file));

            $this->command?->info(sprintf('  [catálogo] %-34s %d filas', $table, DB::table($table)->count()));
        }

        Schema::enableForeignKeyConstraints();
    }
}
