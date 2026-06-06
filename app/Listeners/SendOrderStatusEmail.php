<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\OrderStatusMail;
use App\Services\EmailNotificationService;

class SendOrderStatusEmail
{
    public function __construct(private EmailNotificationService $emailNotifications) {}

    public function handle(OrderStatusChanged $event): void
    {
        if (! $event->notifyCustomer || empty($event->order->customer_email)) {
            return;
        }

        if (! $this->emailNotifications->shouldSendStatusEmail($event->toStatus)) {
            return;
        }

        $this->emailNotifications->queueToCustomer(
            $event->order,
            new OrderStatusMail($event->order, $event->fromStatus, $event->toStatus),
            'email_customer_status_'.$event->toStatus,
        );
    }
}
