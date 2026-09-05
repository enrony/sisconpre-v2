<?php

namespace App\Listeners;

use App\Events\EnviarCorreo;
use Mail;

class EnviaCorreoUsuario
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  EnviarCorreo2  $event
     * @return void
     */
    public function handle(EnviarCorreo $event)
    {
        //
        $data = ['name' => $event->users->name, 'email' => $event->users->email, 'body' => 'Confirmamos su registro exitoso.'];

        Mail::send('emails.mail', $data, function ($message) use ($data) {
            $message->to($data['email'])
                ->subject('Sistema de notificaciones - SISCONPRE');
            $message->from('rollower@gmail.com');
        });
    }
}
