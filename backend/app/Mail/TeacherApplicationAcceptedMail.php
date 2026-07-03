<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class TeacherApplicationAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $applicantName,
        public readonly string $meetLink,
        public readonly Carbon $interviewAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AcaMind Teacher Application - Interview Invitation',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.teacher-application-accepted',
            with: [
                'applicantName' => $this->applicantName,
                'meetLink' => $this->meetLink,
                'interviewAt' => $this->interviewAt,
            ],
        );
    }
}
