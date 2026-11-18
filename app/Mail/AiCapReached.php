<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent once a month, when the proposal assistant reaches its spending cap
 * (story 42, ADR 0014).
 */
class AiCapReached extends Mailable
{
    public function __construct(
        public readonly string $month,
        public readonly string $cap,
        public readonly string $spent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Mission Control: AI drafting paused for {$this->month}");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.ai-cap-reached');
    }
}
