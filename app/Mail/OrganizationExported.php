<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrganizationExported extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $organizationName,
        public string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your export is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.organization.exported-text',
            markdown: 'mail.organization.exported',
            with: [
                'organizationName' => $this->organizationName,
                'link' => $this->link,
            ],
        );
    }
}
