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
        $quien = "{$solicitante?->name} ({$solicitante?->email})";

        return (new MailMessage)
            ->subject($this->solicitud->origen === GrupoTrabajoSolicitud::ORIGEN_REGISTRO
                ? "Nuevo usuario registrado en el grupo {$grupo?->nombre}"
                : "Solicitud para unirse al grupo {$grupo?->nombre}")
            ->greeting('Hola '.($notifiable->name ?? ''))
            ->line($this->solicitud->origen === GrupoTrabajoSolicitud::ORIGEN_REGISTRO
                ? "{$quien} se registró con el código de su grupo de trabajo **{$grupo?->nombre}** y espera su aprobación."
                : "{$quien} pidió unirse a su grupo de trabajo **{$grupo?->nombre}**.")
            ->line('Hasta que la apruebe, no verá ni podrá operar la información del grupo.')
            ->action('Revisar la solicitud', url('/dashboard'))
            ->salutation('GilenSoft');
    }
}
