<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Caracteriza el endpoint del listado de préstamos (`POST /prestamos/records`)
 * y fija sus garantías de rendimiento. Corre contra la BD local `prestamos_gilen`
 * con datos importados (igual que PaymentReportServiceTest); no la refresca.
 *
 * El listado alimenta `PrestamosTable.vue`: datos del préstamo + cliente
 * (nombre/apellido/documento) + `prestamos_dias` para el detalle expandible.
 * NO debe traer toda la tabla ni el snapshot completo del cliente (columna
 * `cliente` JSON, que en filas legadas pesa megabytes).
 */
class PrestamosRecordsTest extends TestCase
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

        $res = $this->postJson('/prestamos/records'.($query ? "?{$query}" : ''));
        $res->assertOk();

        return [$res->json(), $count, strlen($res->getContent())];
    }

    public function test_forma_del_json_del_listado(): void
    {
        [$json] = $this->callRecords();

        foreach (['current_page', 'data', 'per_page', 'total', 'last_page'] as $k) {
            $this->assertArrayHasKey($k, $json['lista'], "falta '{$k}' en el paginador");
        }
        $this->assertSame(10, $json['lista']['per_page']);
        $this->assertLessThanOrEqual(10, count($json['lista']['data']));

        $this->assertNotEmpty($json['lista']['data']);
        $row = $json['lista']['data'][0];

        foreach ([
            'id', 'cliente', 'created', 'date_first_pay', 'date_last_pay',
            'monto_prestamo', 'tasa', 'utilidad', 'total', 'p_estatus',
            'prestamos_dias', 'pause_surcharge',
        ] as $k) {
            $this->assertArrayHasKey($k, $row, "falta '{$k}' en la fila");
        }
        $this->assertArrayHasKey('nombre', $row['cliente']);
        $this->assertArrayHasKey('apellido', $row['cliente']);
    }

    public function test_cliente_liviano_y_cuotas_acotadas(): void
    {
        [$json] = $this->callRecords();
        $row = $json['lista']['data'][0];

        // El cliente es un mini-objeto de identidad, no el snapshot anidado.
        $this->assertArrayNotHasKey('city', $row['cliente']);
        $this->assertArrayNotHasKey('tipo_documento', $row['cliente']);
        $this->assertLessThan(400, strlen((string) json_encode($row['cliente'])));

        if ($row['prestamos_dias'] !== []) {
            $dia = $row['prestamos_dias'][0];
            foreach (['id', 'date', 'sigla', 'cuota', 'apply', 'pagado', 'dom', 'festivo'] as $k) {
                $this->assertArrayHasKey($k, $dia, "falta '{$k}' en la cuota");
            }
        }
    }

    public function test_rendimiento_sin_cargar_toda_la_tabla(): void
    {
        [$json, $queries, $bytes] = $this->callRecords();

        // 4 queries fijas (count + página + prestamos_dias + p_estatus), no N+1
        // ni una segunda pasada por toda la tabla.
        $this->assertLessThan(15, $queries, "demasiadas queries ({$queries})");
        $this->assertLessThan(256 * 1024, $bytes, "payload de {$bytes} bytes: el listado arrastra datos que no usa");
    }

    public function test_paginacion_real(): void
    {
        [$json] = $this->callRecords();
        $esperado = (int) DB::table('prestamos')->count();
        $this->assertSame($esperado, (int) $json['lista']['total']);
    }

    public function test_orden_ascendente_por_id(): void
    {
        [$json] = $this->callRecords('sortColumn=id&sortOrder=asc');
        $ids = array_column($json['lista']['data'], 'id');

        $ordenado = $ids;
        sort($ordenado);
        $this->assertSame($ordenado, $ids);
    }

    public function test_filtro_estados(): void
    {
        [$json] = $this->callRecords('estados=1');

        foreach ($json['lista']['data'] as $row) {
            $this->assertSame(1, (int) $row['estatus']);
        }
        $esperado = (int) DB::table('prestamos')->where('estatus', 1)->count();
        $this->assertSame($esperado, (int) $json['lista']['total']);
    }
}
