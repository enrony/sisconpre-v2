<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Caracteriza el endpoint del listado de informes de pago (`GET /payment_report/records`)
 * y fija sus garantías de rendimiento. Corre contra la BD local `prestamos_gilen`
 * con datos importados (igual que PaymentReportServiceTest); no la refresca ni la ensucia.
 *
 * El listado sólo alimenta la tabla de `PaymentReportTable.vue`: id, cliente
 * (nombre/apellido), created, destino, monto, nº de cuotas y estado. NO debe
 * arrastrar el árbol de préstamos/cuotas ni el snapshot completo del cliente
 * (columna `cliente` JSON, que en filas legadas pesa megabytes).
 */
class PaymentReportRecordsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'prestamos_gilen',
        ]);
        DB::purge('mysql');

        if (! DB::table('users')->where('email', 'enrony@gmail.com')->exists()) {
            $this->markTestSkipped('BD local sin datos importados.');
        }

        $this->actingAs(User::where('email', 'enrony@gmail.com')->firstOrFail());
    }

    /** @return array{0:mixed,1:int,2:int} [json, queryCount, contentBytes] */
    private function callRecords(string $query = ''): array
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $res = $this->getJson('/payment_report/records'.($query ? "?{$query}" : ''));
        $res->assertOk();

        return [$res->json(), $count, strlen($res->getContent())];
    }

    public function test_forma_del_json_del_listado(): void
    {
        [$json] = $this->callRecords();

        foreach (['current_page', 'data', 'per_page', 'total', 'last_page'] as $k) {
            $this->assertArrayHasKey($k, $json['lista'], "falta '{$k}' en el paginador");
        }

        $this->assertNotEmpty($json['lista']['data'], 'la BD importada debería traer informes');
        $row = $json['lista']['data'][0];

        foreach ([
            'id', 'cliente', 'created', 'destination', 'destination_text',
            'value_amount', 'number_cuotas', 'status_description',
        ] as $k) {
            $this->assertArrayHasKey($k, $row, "falta '{$k}' en la fila");
        }

        $this->assertArrayHasKey('nombre', $row['cliente']);
        $this->assertArrayHasKey('apellido', $row['cliente']);
        foreach (['id', 'desc', 'color', 'finish_estatus'] as $k) {
            $this->assertArrayHasKey($k, $row['status_description']);
        }
    }

    public function test_el_cliente_del_listado_es_liviano_sin_arbol_de_prestamos(): void
    {
        [$json] = $this->callRecords();
        $row = $json['lista']['data'][0];

        // Nada de árbol de préstamos/cuotas en la fila del listado.
        $this->assertArrayNotHasKey('prestamoFormated', $row);
        $this->assertArrayNotHasKey('selected_payment_reports', $row);

        // El cliente es un mini-objeto de identidad, no el snapshot anidado.
        $this->assertArrayNotHasKey('city', $row['cliente']);
        $this->assertArrayNotHasKey('tipo_documento', $row['cliente']);
        $this->assertLessThan(400, strlen((string) json_encode($row['cliente'])));
    }

    public function test_rendimiento_pocas_queries_y_payload_chico(): void
    {
        [$json, $queries, $bytes] = $this->callRecords();

        $rows = count($json['lista']['data']);

        // Sin N+1: un puñado de queries fijas, no ~9 por fila.
        $this->assertLessThan(20, $queries, "demasiadas queries ({$queries}) para {$rows} filas — hay N+1");

        // El payload no puede escalar con el tamaño de los préstamos ni con los
        // snapshots de cliente legados (que llegan a 2,8 MB en una sola fila).
        $this->assertLessThan(256 * 1024, $bytes, "payload de {$bytes} bytes: el listado está arrastrando datos que no usa");
    }

    public function test_filtro_destino_pago(): void
    {
        [$json] = $this->callRecords('destinoPago=2');

        foreach ($json['lista']['data'] as $row) {
            $this->assertSame(2, (int) $row['destination']);
        }
    }

    public function test_filtro_cliente(): void
    {
        $cliId = DB::table('payment_reports')->where('estatus', 1)
            ->selectRaw('cliente_id, count(*) c')->groupBy('cliente_id')
            ->orderByDesc('c')->value('cliente_id');

        [$json] = $this->callRecords("clientes={$cliId}");

        $this->assertNotEmpty($json['lista']['data']);
        $esperado = (int) DB::table('payment_reports')
            ->where('estatus', 1)->where('cliente_id', $cliId)->count();
        $this->assertSame($esperado, (int) $json['lista']['total']);
    }
}
