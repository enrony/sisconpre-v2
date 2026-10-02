<?php

namespace App\Notifications;

use App\Models\GrupoTrabajoSolicitud;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Al dueño del grupo: alguien pidió unirse con el código del grupo. */
class SolicitudUnionGrupo extends Notification
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
        $grupo = $this->solicitud->grupo;
        $solicitante = $this->solicitud->solicitante;

        return (new MailMessage)
            ->subject("Solicitud para unirse al grupo {$grupo?->nombre}")
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->line("{$solicitante?->name} ({$solicitante?->email}) pidió unirse a su grupo de trabajo **{$grupo?->nombre}**.")
            ->line('Hasta que la apruebe, no verá ni podrá operar la información del grupo.')
            ->action('Revisar la solicitud', url('/dashboard'))
            ->salutation('GilenSoft');
    }
}
