<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportar a Excel/PDF, reutilizable por cualquier `Reporte*Controller`: cada
 * uno solo arma sus filas (ya "aplanadas", un array asociativo por fila) y su
 * mapa de columnas (clave del array => etiqueta visible), y llama a uno de
 * estos dos métodos. No se acopla a Eloquent ni a paginación — quien lo use
 * decide qué trae (típicamente la misma query filtrada de su listado, sin
 * `paginate()`, para exportar TODO lo filtrado, no solo la página visible).
 */
trait ExportsReport
{
    /**
     * @param  iterable<int, array<string, mixed>>  $filas
     * @param  array<string, string>  $columnas  clave de la fila => etiqueta de columna
     */
    protected function generarExcel(iterable $filas, array $columnas, string $nombreArchivo): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $claves = array_keys($columnas);
        $datos = [array_values($columnas)];

        foreach ($filas as $fila) {
            $datos[] = array_map(static fn (string $clave): mixed => $fila[$clave] ?? null, $claves);
        }

        $sheet->fromArray($datos);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, "{$nombreArchivo}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $filas
     * @param  array<string, string>  $columnas  clave de la fila => etiqueta de columna
     * @param  bool  $inline  true = lo muestra en el navegador (usado por "Imprimir",
     *                        que abre esto en una pestaña y deja que el usuario imprima
     *                        desde el visor de PDF); false = fuerza la descarga.
     */
    protected function generarPdf(iterable $filas, array $columnas, string $nombreArchivo, string $titulo, bool $inline = false): Response
    {
        $pdf = Pdf::loadView('reportes.pdf', [
            'titulo' => $titulo,
            'columnas' => $columnas,
            'filas' => $filas,
        ])->setPaper('a4', 'landscape');

        return $inline ? $pdf->stream("{$nombreArchivo}.pdf") : $pdf->download("{$nombreArchivo}.pdf");
    }
}
