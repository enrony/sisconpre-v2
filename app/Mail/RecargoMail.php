<?php

namespace App\Mail;

use App\Models\PrestamosDias;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecargoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $prestamoDia;

    public function __construct(PrestamosDias $prestamoDia)
    {
        $this->prestamoDia = $prestamoDia;
    }

    public function build()
    {
        return $this->view('emails.recargo')
            ->subject('Notificación de Recargo en su Préstamo')
            ->with([
                'prestamoDia' => $this->prestamoDia,
            ]);
    }
}
