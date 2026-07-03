<?php

namespace App\Mail;

use App\Models\ProgressReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyProgressReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ProgressReport $report)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AcaMind Weekly Progress Report for ' . $this->report->student->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-progress-report',
            with: ['report' => $this->report],
        );
    }
}
