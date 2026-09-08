<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Compacta la columna JSON `payment_reports.cliente`.
 *
 * En el sistema legado el front enviaba el objeto cliente COMPLETO (con `city`
 * → `department` → `country`, `tipo_documento`, etc.) y el mutator lo guardaba
 * tal cual. Algunas filas importadas pesan cientos de KB / varios MB, lo que
 * infla cualquier consulta que las lea. El front nuevo ya manda un cliente
 * mínimo; este comando recorta las filas viejas al mismo tamaño: conserva sólo
 * las claves escalares de primer nivel (id, documento, nombre, apellido, …) y
 * descarta cualquier valor anidado (arrays/objetos).
 *
 * Idempotente y seguro de re-correr. Usar `--dry-run` primero.
 */
class CompactarClientesPaymentReports extends Command
{
    protected $signature = 'payment-report:compactar-clientes {--dry-run : No escribe; sólo informa qué filas se tocarían}';

    protected $description = 'Recorta payment_reports.cliente a sus campos escalares (quita el árbol anidado del cliente)';

    /** A partir de este tamaño la fila se considera candidata a compactar. */
    private const UMBRAL_BYTES = 8192;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $filas = DB::table('payment_reports')
            ->select('id', 'cliente')
            ->whereNotNull('cliente')
            ->orderBy('id')
            ->get();

        $tocadas = 0;
        $ahorro = 0;

        foreach ($filas as $fila) {
            $original = (string) $fila->cliente;

            // Sólo las filas realmente infladas; las normales (~1-2 KB) se dejan
            // intactas byte a byte para no alterar datos que el front pudiera leer.
            if (strlen($original) < self::UMBRAL_BYTES) {
                continue;
            }

            $datos = json_decode($original, true);
            if (! is_array($datos)) {
                $this->warn("  #{$fila->id}: `cliente` no es JSON de objeto — se omite.");

                continue;
            }

            $plano = array_filter($datos, static fn ($v) => ! is_array($v));

            $nuevo = json_encode($plano, JSON_UNESCAPED_UNICODE);
            if ($nuevo === false) {
                $this->warn("  #{$fila->id}: no se pudo re-serializar — se omite.");

                continue;
            }

            $delta = strlen($original) - strlen($nuevo);

            // Nada anidado que quitar (o ya compactada): sin cambios.
            if (count($plano) === count($datos) || $delta <= 0) {
                continue;
            }

            $tocadas++;
            $ahorro += $delta;

            $this->line(sprintf(
                '  #%d: %s → %s bytes  (−%s)  claves %d → %d',
                $fila->id,
                number_format(strlen($original)),
                number_format(strlen($nuevo)),
                number_format($delta),
                count($datos),
                count($plano),
            ));

            if (! $dryRun) {
                DB::table('payment_reports')->where('id', $fila->id)->update(['cliente' => $nuevo]);
            }
        }

        $this->newLine();
        $resumen = sprintf(
            '%s%d fila(s) %s, %s bytes %s.',
            $dryRun ? '[dry-run] ' : '',
            $tocadas,
            $dryRun ? 'se compactarían' : 'compactadas',
            number_format($ahorro),
            $dryRun ? 'se ahorrarían' : 'ahorrados',
        );

        if ($tocadas === 0) {
            $this->info('Nada que compactar: todas las filas ya están planas o por debajo del umbral.');

            return self::SUCCESS;
        }

        $this->info($resumen);

        if ($dryRun) {
            $this->comment('Re-ejecutá sin --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
