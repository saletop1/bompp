<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SapUploadNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Properti publik ini akan otomatis tersedia di dalam file view email.
     */
    public $results;

    /**
     * Create a new message instance.
     */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Status Unggah BOM ke SAP',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Memberitahu Laravel untuk menggunakan file view ini untuk konten email
        return new Content(
            view: 'emails.sap_upload_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
