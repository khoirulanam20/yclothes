<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingVerification = 'awaiting_verification';
    case Confirmed = 'confirmed';
    case Processed = 'processed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Return = 'return';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::AwaitingVerification => 'Menunggu Verifikasi',
            self::Confirmed => 'Dikonfirmasi',
            self::Processed => 'Diproses',
            self::Shipped => 'Dikirim',
            self::Delivered => 'Diterima',
            self::Completed => 'Selesai',
            self::Return => 'Retur',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
