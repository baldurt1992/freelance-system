<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BillingDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

final class BillingDocumentMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly BillingDocument $document,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cuenta de cobro {$this->document->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'billing.email',
            with: [
                'document' => $this->document,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->document->pdf_path === null) {
            return [];
        }

        $fullPath = Storage::disk('local')->path($this->document->pdf_path);

        return [
            Attachment::fromPath($fullPath)
                ->as("{$this->document->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
