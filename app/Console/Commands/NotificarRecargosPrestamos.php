<?php

namespace App\Console\Commands;

use App\Mail\RecargoMail;
use App\Models\PrestamosDias;
use App\Models\ScheduledTaskLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class NotificarRecargosPrestamos extends Command
{
    protected $signature = 'prestamos:notificar-recargos';

    protected $description = 'Envía notificaciones de recargo a los clientes a las 8 AM en su zona horaria.';

    public function handle()
    {
        $taskName = 'notificar-recargos';
        $taskLog = null;
        $recordsProcessed = 0;
        $successful = true;
        $errorMessage = null;

        // Verificar si ya hay una instancia en ejecución o pendiente
        $existingTask = ScheduledTaskLog::where('task_name', $taskName)
            ->where('status', false)
            ->first();

        if ($existingTask) {
            $this->info("La tarea '{$taskName}' ya está en ejecución o pendiente. Se omitirá esta ejecución.");

            return;
        }

        // Crear un nuevo registro de inicio de la tarea
        $taskLog = ScheduledTaskLog::create([
            'task_name' => $taskName,
            'started_at' => Carbon::now(),
            'status' => false, // Marcada como en ejecución
        ]);

        try {
            $prestamosDias = PrestamosDias::where('is_surcharge', true)
                ->where('notified_surcharge', false)
                ->whereHas('Prestamo', function ($query) {
                    // Legado: usaba la columna inexistente `estado` (es `estatus`).
                    $query->where('estatus', 1);
                })
                ->with(['Prestamo.datoCliente' => function ($query) {
                    $query->select('id', 'nombre', 'apellido', 'email')
                        ->whereNotNull('email_verified_at');
                }, 'Prestamo.country' => function ($query) {
                    $query->select('id', 'timezone');
                }])
                ->get();

            $prestamosPorTimezone = $prestamosDias->groupBy('Prestamo.country.timezone');

            foreach ($prestamosPorTimezone as $timezone => $prestamosEnTimezone) {
                $nowInTimezone = Carbon::now($timezone);

                if ($nowInTimezone->hour == 8 && $nowInTimezone->minute >= 0 && $nowInTimezone->minute < 2) {
                    foreach ($prestamosEnTimezone as $prestamoDia) {
                        $cliente = $prestamoDia->Prestamo->datoCliente;

                        if ($cliente) {
                            try {
                                $mailerName = config('mail.notifications_mailer');
                                $mail = new RecargoMail($prestamoDia);
                                if ($mailerName === 'smtp2') {
                                    $from = config('mail.from2');
                                    $mail->from($from['address'], $from['name']);
                                }
                                Mail::mailer($mailerName)->to($cliente->email)->send($mail);
                                $prestamoDia->notified_surcharge = true;
                                $prestamoDia->date_surcharge_notified = Carbon::now();
                                $prestamoDia->error_sending_notification = 0;
                                $prestamoDia->save();
                                $recordsProcessed++;
                                $this->info("Notificación enviada a {$cliente->email} ({$timezone})");
                            } catch (\Exception $e) {
                                $prestamoDia->error_sending_notification = min($prestamoDia->error_sending_notification + 1, 255);
                                $prestamoDia->save();
                                $this->error("Error al enviar notificación a {$cliente->email} ({$timezone}): {$e->getMessage()}");
                            }
                        }
                    }
                }
            }

        } catch (\Exception $exception) {
            $successful = false;
            $errorMessage = $exception->getMessage();
            $this->error("Error general en la tarea '{$taskName}': {$errorMessage}");
        } finally {
            // Actualizar el registro de la tarea al finalizar
            if ($taskLog) {
                $taskLog->update([
                    'finished_at' => Carbon::now(),
                    'successful' => $successful,
                    'error_message' => $errorMessage,
                    'records_processed' => $recordsProcessed,
                    'status' => true, // Marcada como terminada
                ]);
            }
        }

        $this->info("Proceso de notificación de recargos completado. Registros procesados: {$recordsProcessed}.");
    }
}
