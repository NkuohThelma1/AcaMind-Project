<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $applicantName,
        public readonly ?string $reason = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AcaMind Teacher Application',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.teacher-application-rejected',
            with: ['applicantName' => $this->applicantName, 'reason' => $this->reason],
        );
    }
}
