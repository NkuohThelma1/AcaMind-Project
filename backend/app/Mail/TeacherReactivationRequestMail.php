<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherReactivationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $teacherName,
        public readonly string $teacherEmail,
        public readonly string $message,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Teacher Reactivation Request - {$this->teacherName}",
            replyTo: [$this->teacherEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.teacher-reactivation-request',
            with: [
                'teacherName' => $this->teacherName,
                'teacherEmail' => $this->teacherEmail,
                'message' => $this->message,
            ],
        );
    }
}
