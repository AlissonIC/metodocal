<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mailable genérico usado pelo NotificationQueueService para enviar
 * um corpo HTML já renderizado (vindo da fila).
 */
class QueuedEmail extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody);
    }
}
