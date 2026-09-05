<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Importa los datos de tenant del sistema legado al esquema nuevo.
 *
 * Origen: conexión `mysql_ref` (BD `prestamos_ref`, cargada desde `prestamos_db.sql`).
 * Destino: conexión por defecto.
 *
 * NO toca los catálogos (los pone LegacyCatalogSeeder) ni las tablas del
 * framework/spatie. El RBAC propio (`modules`, `profiles`, …) se importa aquí
 * y luego se consolida en spatie con otro comando (PLAN_MIGRACION.md §11).
 *
 * Idempotente: vacía cada tabla destino y la recarga. Copia server-side
 * (`INSERT ... SELECT` entre esquemas) con las FKs desactivadas.
 */
class LegacyImportData extends Command
{
    protected $signature = 'legacy:import-data {--pretend : Solo muestra lo que haría}';

    protected $description = 'Importa datos de tenant desde la BD del volcado legado (mysql_ref) al esquema nuevo';

    /**
     * Tablas de tenant a importar, en orden lógico (el orden no es crítico:
     * se copian con FOREIGN_KEY_CHECKS=0).
     *
     * @var list<string>
     */
    private const TABLES = [
        // organización / usuarios
        'users',
        'grupos_trabajos',
        'grupos_trabajos_users',
        'user_countries',
        'franquicias',
        // clientes
        'clientes',
        'clientes_grupos_trabajos_users',
        // préstamos
        'prestamos',
        'prestamos_dias',
        'prestamos_tarifas',
        // informes de pago
        'payment_reports',
        'payment_reports_methods',
        'payment_reports_movements',
        'payment_reports_support_movements',
        'selected_payment_reports',
        'support_payment_reports',
        'support_payment_reports_methods',
        // movimientos de cliente
        'customer_movement_histories',
        'summary_customer_movements',
        // varios
        'configuration_items',
        'scheduled_tasks_log',
        // RBAC propio (se consolidará en spatie — §11)
        'modules',
        'modules_actions',
        'modules_relations',
        'profiles',
        'modules_actions_profiles',
        'users_profiles',
    ];

    /**
     * Filtros WHERE aplicados al SELECT del origen (excluir basura de pruebas).
     *
     * @var array<string, string>
     */
    private const FILTERS = [
        'profiles' => self::KEEP_PROFILE,
        // no arrastrar asignaciones de los perfiles de prueba excluidos
        'modules_actions_profiles' => 'profiles_id IN (SELECT id FROM profiles WHERE '.self::KEEP_PROFILE.')',
        'users_profiles' => 'profiles_id IN (SELECT id FROM profiles WHERE '.self::KEEP_PROFILE.')',
    ];

    private const KEEP_PROFILE = "LOWER(name) NOT IN ('prueba', 'testing', 'test')";

    public function handle(): int
    {
        $target = DB::connection()->getDatabaseName();
        $ref = DB::connection('mysql_ref')->getDatabaseName();

        try {
            DB::connection('mysql_ref')->getPdo();
        } catch (\Throwable $e) {
            $this->error("No se puede conectar a la BD de referencia '{$ref}' (conexión mysql_ref): {$e->getMessage()}");
            $this->line('Carga el volcado primero:  mysql -u root prestamos_ref < prestamos_db.sql');

            return self::FAILURE;
        }

        $this->info("Origen:  {$ref}  (mysql_ref)");
        $this->info("Destino: {$target}");
        $this->newLine();

        $pretend = (bool) $this->option('pretend');

        if (! $pretend) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->delete();
            }
        }

        $total = 0;

        foreach (self::TABLES as $table) {
            $refCols = Schema::connection('mysql_ref')->hasTable($table)
                ? Schema::connection('mysql_ref')->getColumnListing($table)
                : [];
            $dstCols = Schema::hasTable($table)
                ? Schema::getColumnListing($table)
                : [];

            if ($refCols === [] || $dstCols === []) {
                $this->warn(sprintf('  %-34s tabla ausente en origen o destino — se omite', $table));

                continue;
            }

            $cols = array_values(array_intersect($dstCols, $refCols));
            $colList = implode(', ', array_map(fn ($c) => "`{$c}`", $cols));
            $where = isset(self::FILTERS[$table]) ? ' WHERE '.self::FILTERS[$table] : '';

            $sql = "INSERT INTO `{$target}`.`{$table}` ({$colList}) SELECT {$colList} FROM `{$ref}`.`{$table}`{$where}";

            if ($pretend) {
                $this->line("  {$sql}");

                continue;
            }

            DB::statement($sql);
            $count = DB::table($table)->count();
            $total += $count;
            $this->info(sprintf('  %-34s %6d filas', $table, $count));
        }

        if (! $pretend) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->newLine();
            $this->info("Importadas {$total} filas en ".count(self::TABLES).' tablas.');
        }

        return self::SUCCESS;
    }
}
