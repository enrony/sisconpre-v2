<?php

namespace App\Notifications;

use App\Models\GrupoTrabajoSolicitud;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Al solicitante: el dueño del grupo aprobó o rechazó su solicitud. */
class SolicitudUnionGrupoResuelta extends Notification
{
    public function __construct(public GrupoTrabajoSolicitud $solicitud) {}

    /**
     * Canales de aviso. WhatsApp se suma acá cuando esté el canal.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $grupo = $this->solicitud->grupo?->nombre;
        $aprobada = $this->solicitud->estatus === GrupoTrabajoSolicitud::APROBADA;

        $mensaje = (new MailMessage)
            ->subject($aprobada ? "Ya forma parte del grupo {$grupo}" : "Su solicitud al grupo {$grupo} no fue aprobada")
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->salutation('GilenSoft');

        return $aprobada
            ? $mensaje
                ->line("Su solicitud para unirse al grupo de trabajo **{$grupo}** fue aprobada.")
                ->line('Puede elegirlo en el selector de grupo, arriba a la derecha.')
                ->action('Entrar', url('/dashboard'))
            : $mensaje
                ->line("Su solicitud para unirse al grupo de trabajo **{$grupo}** no fue aprobada.")
                ->line('Si cree que es un error, consúltelo con quien le compartió el código.');
    }
}
