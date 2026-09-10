<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Compacta la columna JSON `cliente` en `payment_reports` y `prestamos`.
 *
 * En el sistema legado el front enviaba el objeto cliente COMPLETO (con `city`
 * → `department` → `country`, `tipo_documento`, arrays de préstamos, etc.) y el
 * mutator lo guardaba tal cual. Algunas filas importadas pesan cientos de KB /
 * varios MB, lo que infla cualquier consulta que las lea. El front nuevo ya
 * manda un cliente mínimo; este comando recorta las filas viejas al mismo
 * tamaño: conserva sólo las claves escalares de primer nivel (id, documento,
 * nombre, apellido, …) y descarta cualquier valor anidado (arrays/objetos).
 *
 * Idempotente y seguro de re-correr. Usar `--dry-run` primero.
 */
class CompactarClientesPaymentReports extends Command
{
    protected $signature = 'datos:compactar-clientes {--dry-run : No escribe; sólo informa qué filas se tocarían}';

    protected $description = 'Recorta la columna `cliente` (JSON) de payment_reports y prestamos a sus campos escalares';

    /** Tablas con una columna `cliente` JSON heredada del legado. */
    private const TABLAS = ['payment_reports', 'prestamos'];

    /** A partir de este tamaño la fila se considera candidata a compactar. */
    private const UMBRAL_BYTES = 8192;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $totalTocadas = 0;
        $totalAhorro = 0;

        foreach (self::TABLAS as $tabla) {
            $this->line("<info>{$tabla}</info>");

            $filas = DB::table($tabla)
                ->select('id', 'cliente')
                ->whereNotNull('cliente')
                ->orderBy('id')
                ->get();

            $tocadas = 0;
            $ahorro = 0;

            foreach ($filas as $fila) {
                $original = (string) $fila->cliente;

                // Sólo las filas realmente infladas; las normales (~1-2 KB) se
                // dejan intactas byte a byte para no alterar datos que el front
                // pudiera leer.
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
                    DB::table($tabla)->where('id', $fila->id)->update(['cliente' => $nuevo]);
                }
            }

            $totalTocadas += $tocadas;
            $totalAhorro += $ahorro;

            $this->line($tocadas === 0
                ? '  (nada que compactar)'
                : sprintf('  %d fila(s), %s bytes', $tocadas, number_format($ahorro)));
        }

        $this->newLine();
        $this->info(sprintf(
            '%s%d fila(s) %s, %s bytes %s en total.',
            $dryRun ? '[dry-run] ' : '',
            $totalTocadas,
            $dryRun ? 'se compactarían' : 'compactadas',
            number_format($totalAhorro),
            $dryRun ? 'se ahorrarían' : 'ahorrados',
        ));

        if ($dryRun && $totalTocadas > 0) {
            $this->comment('Re-ejecutá sin --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
