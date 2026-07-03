<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherAccountDeactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $teacherName,
        public readonly int $inactivityDays,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AcaMind Teacher Account Has Been Deactivated',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.teacher-account-deactivated',
            with: ['teacherName' => $this->teacherName, 'inactivityDays' => $this->inactivityDays],
        );
    }
}
