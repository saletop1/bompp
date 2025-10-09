<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RoutingDocumentComplete extends Mailable
{
    use Queueable, SerializesModels;

    public $documentDetails;

    /**
     * Create a new message instance.
     *
     * @param array $documentDetails
     * @return void
     */
    public function __construct(array $documentDetails)
    {
        $this->documentDetails = $documentDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Notifikasi: Dokumen Routing ' . $this->documentDetails['document_number'] . ' Telah Selesai Diunggah';

        return $this->subject($subject)
                    ->markdown('emails.routing_complete'); // Kita akan membuat view ini selanjutnya
    }
}
