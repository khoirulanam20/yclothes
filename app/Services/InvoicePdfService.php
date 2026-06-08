<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function generate(Order $order, string $heading): string
    {
        $order->loadMissing('items');

        return Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'heading' => $heading,
        ])
            ->setPaper('a4')
            ->output();
    }

    public function filename(Order $order): string
    {
        return 'faktur-'.$order->order_number.'.pdf';
    }
}
