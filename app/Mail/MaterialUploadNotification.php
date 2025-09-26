<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaterialUploadNotification extends Mailable
{
    use Queueable, SerializesModels;

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
            subject: 'Laporan Status Pembuatan Material Master',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Mengarahkan Mailable ini untuk menggunakan view blade yang baru kita buat
        return new Content(
            view: 'emails.material_upload_notification',
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
