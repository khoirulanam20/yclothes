<?php

namespace App\Mail;

use App\Enums\InvoiceEmailContext;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $heading;

    public string $intro;

    public function __construct(
        public Order $order,
        public InvoiceEmailContext $context = InvoiceEmailContext::Paid,
    ) {
        $this->heading = match ($this->context) {
            InvoiceEmailContext::Created => 'Faktur Proforma #'.$order->order_number,
            InvoiceEmailContext::Paid => 'Faktur Pembayaran #'.$order->order_number,
        };

        $this->intro = match ($this->context) {
            InvoiceEmailContext::Created => 'Berikut faktur proforma untuk pesanan Anda.',
            InvoiceEmailContext::Paid => 'Pembayaran pesanan Anda telah dikonfirmasi. Berikut faktur pembayaran Anda.',
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.invoice',
            with: [
                'heading' => $this->heading,
                'intro' => $this->intro,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $pdfService = app(InvoicePdfService::class);

        return [
            Attachment::fromData(
                fn () => $pdfService->generate($this->order, $this->heading),
                $pdfService->filename($this->order),
            )->withMime('application/pdf'),
        ];
    }
}
