<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas (portadas de app/Console/Kernel.php del sistema legado)
|--------------------------------------------------------------------------
| En producción (Ferozo) el cron debe invocar:
|   /usr/local/lsws/lsphp83/bin/php /home/gilen/public_html/prestamos_app/artisan schedule:run
*/

Schedule::command('holiday:cron')->yearly();
Schedule::command('prestamos:aplicar-recargos')->daily();
Schedule::command('prestamos:notificar-recargos')->everyMinute()->withoutOverlapping();
