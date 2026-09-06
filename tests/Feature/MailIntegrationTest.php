<?php

namespace Tests\Feature;

use App\Mail\RecargoMail;
use App\Models\PrestamosDias;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Integraciones de correo (Fase 8): la plantilla `emails.recargo` renderiza y
 * el mailer secundario `smtp2` está configurado.
 */
class MailIntegrationTest extends TestCase
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
    }

    public function test_recargo_mail_renderiza(): void
    {
        $id = DB::table('prestamos_dias')->orderByDesc('id')->value('id');
        $pd = PrestamosDias::findOrFail($id);

        $html = mb_strtolower((new RecargoMail($pd))->render());

        $this->assertStringContainsString('recargo por mora', $html);
        $this->assertStringContainsString('préstamo', $html);
        $this->assertStringContainsString('cuota con recargo', $html);
    }

    public function test_mailer_secundario_configurado(): void
    {
        $this->assertIsArray(config('mail.mailers.smtp2'));
        $this->assertSame('smtp', config('mail.mailers.smtp2.transport'));
        $this->assertIsArray(config('mail.from2'));
        $this->assertIsString(config('mail.notifications_mailer'));

        // Se puede resolver sin excepción.
        Mail::fake();
        Mail::mailer('smtp2');
        $this->assertTrue(true);
    }
}
