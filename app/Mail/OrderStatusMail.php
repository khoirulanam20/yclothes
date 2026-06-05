<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $fromStatus,
        public string $toStatus,
    ) {}

    public function envelope(): Envelope
    {
        $labels = [
            'pending' => 'Menunggu Pembayaran',
            'awaiting_verification' => 'Menunggu Verifikasi',
            'confirmed' => 'Dikonfirmasi',
            'processed' => 'Sedang Diproses',
            'shipped' => 'Dikirim',
            'delivered' => 'Sampai Tujuan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];

        $label = $labels[$this->toStatus] ?? $this->toStatus;

        return new Envelope(
            subject: 'Update Pesanan #'.$this->order->order_number.' — '.$label,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status',
        );
    }
}
