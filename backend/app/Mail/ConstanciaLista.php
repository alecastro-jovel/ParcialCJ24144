<?php

namespace App\Mail;

use App\Models\Constancia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConstanciaLista extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Constancia $constancia)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu constancia está lista');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.constancia_lista');
    }
}
