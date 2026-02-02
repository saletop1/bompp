<?php

namespace App\Mail;

use App\Models\MaterialImage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaterialImageUploaded extends Mailable
{
    use Queueable, SerializesModels;

    public $materialImage;

    /**
     * Create a new message instance.
     */
    public function __construct(MaterialImage $materialImage)
    {
        $this->materialImage = $materialImage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Gambar Material Berhasil Diupload - ' . $this->materialImage->material_code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.material_image_uploaded',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
