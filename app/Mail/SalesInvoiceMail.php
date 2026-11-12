<?php

namespace App\Mail;

use App\Models\Sales;
use App\Services\SalesService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Customer-facing invoice email: the invoice PDF as attachment plus an
 * itemised table in the body. Sent synchronously so the clicker sees the
 * send failure in the flash message instead of a queued job log.
 */
class SalesInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sales $sale;

    public function __construct(Sales $sale)
    {
        $this->sale = $sale;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.invoice_email_subject', [
                'invoice' => $this->sale->invoice,
                'company' => config('app.name'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.sales-invoice',
            with: [
                'items' => $this->sale->salesItems()->with('product')->get(),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(SalesService::class)->renderInvoicePdf($this->sale->id)['contents'],
                'invoice-'.$this->sale->invoice.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
