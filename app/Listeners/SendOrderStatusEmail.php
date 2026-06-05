<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\OrderStatusMail;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusEmail
{
    public function handle(OrderStatusChanged $event): void
    {
        if (empty($event->order->customer_email)) {
            return;
        }

        Mail::to($event->order->customer_email)->queue(
            new OrderStatusMail($event->order, $event->fromStatus, $event->toStatus),
        );
    }
}
