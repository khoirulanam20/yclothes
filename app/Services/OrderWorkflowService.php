<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Mail\AdminNewOrderMail;
use App\Mail\AdminPaymentSubmittedMail;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use InvalidArgumentException;

class OrderWorkflowService
{
    public function __construct(private EmailNotificationService $emailNotifications) {}
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        OrderStatus::Pending->value => ['awaiting_verification', 'confirmed', 'cancelled'],
        OrderStatus::AwaitingVerification->value => ['confirmed', 'cancelled', 'pending'],
        OrderStatus::Confirmed->value => ['processed', 'cancelled'],
        OrderStatus::Processed->value => ['shipped', 'cancelled'],
        OrderStatus::Shipped->value => ['delivered', 'cancelled'],
        OrderStatus::Delivered->value => ['completed', 'cancelled', 'return'],
        OrderStatus::Completed->value => ['return'],
        OrderStatus::Return->value => ['completed'],
        OrderStatus::Cancelled->value => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(
        Order $order,
        string $toStatus,
        ?string $note = null,
        ?string $actorType = null,
        ?int $actorId = null,
        bool $notifyCustomer = true,
        array $extra = [],
    ): Order {
        $fromStatus = $order->order_status;

        if ($fromStatus === $toStatus && empty($extra)) {
            return $order;
        }

        if ($fromStatus !== $toStatus && ! $this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidArgumentException("Transisi status tidak valid: {$fromStatus} → {$toStatus}");
        }

        $payload = array_merge(['order_status' => $toStatus], $extra);

        if ($toStatus === OrderStatus::Delivered->value && ! $order->delivered_at) {
            $payload['delivered_at'] = now();
        }

        if ($toStatus === OrderStatus::Completed->value && ! $order->completed_at) {
            $payload['completed_at'] = now();
        }

        $order->updateTrusted($payload);

        if ($toStatus === 'cancelled' && $fromStatus !== 'cancelled') {
            app(InventoryService::class)->releaseForOrder($order->fresh(), $note ?? 'Pesanan dibatalkan');
        }

        if ($fromStatus !== $toStatus) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'note' => $note,
                'notify_customer' => $notifyCustomer,
            ]);

            event(new OrderStatusChanged($order->fresh(), $fromStatus, $toStatus, $notifyCustomer));
        }

        return $order->fresh();
    }

    public function recordInitialStatus(Order $order): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => $order->order_status,
            'actor_type' => 'system',
            'note' => 'Pesanan dibuat',
            'notify_customer' => false,
        ]);
    }

    public function notifyAdminNewOrder(Order $order): void
    {
        AdminNotification::notify(
            'order_created',
            'Pesanan Baru #'.$order->order_number,
            'Total: Rp '.number_format($order->grand_total, 0, ',', '.'),
            ['order_id' => $order->id, 'order_number' => $order->order_number],
        );

        $this->emailNotifications->queueToAdmins(new AdminNewOrderMail($order), 'email_admin_new_order');
    }

    public function notifyAdminPaymentSubmitted(Order $order): void
    {
        AdminNotification::notify(
            'payment_submitted',
            'Konfirmasi Pembayaran #'.$order->order_number,
            'Pembeli mengajukan konfirmasi transfer.',
            ['order_id' => $order->id, 'order_number' => $order->order_number],
        );

        $this->emailNotifications->queueToAdmins(new AdminPaymentSubmittedMail($order), 'email_admin_payment_submitted');
    }
}
