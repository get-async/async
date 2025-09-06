<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ApiKeyDestroyed extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $label,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'API key removed',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.api.destroyed-text',
            markdown: 'mail.api.destroyed',
            with: [
                'label' => $this->label,
            ],
        );
    }
}
