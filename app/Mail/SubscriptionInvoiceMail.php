<?php

namespace App\Mail;

use App\Models\SubscriptionOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public SubscriptionOrder $order;
    public string $planName;
    public float $priceUsd;
    public int $priceIdr;
    public string $tenantName;

    /**
     * Create a new message instance.
     */
    public function __construct(SubscriptionOrder $order)
    {
        $this->order = $order;
        $this->planName = $order->plan->name;
        $this->priceUsd = (float) $order->plan->price;
        $this->priceIdr = (int) $order->amount;
        $this->tenantName = $order->tenant->name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmed - ' . $this->planName . ' Plan | ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-invoice',
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
