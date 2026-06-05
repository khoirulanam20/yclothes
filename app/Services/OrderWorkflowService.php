<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class OrderWorkflowService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'pending' => ['awaiting_verification', 'confirmed', 'cancelled'],
        'awaiting_verification' => ['confirmed', 'cancelled', 'pending'],
        'confirmed' => ['processed', 'cancelled'],
        'processed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => ['completed', 'cancelled', 'return'],
        'completed' => ['return'],
        'return' => ['completed'],
        'cancelled' => [],
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

        if ($toStatus === 'delivered' && ! $order->delivered_at) {
            $payload['delivered_at'] = now();
        }

        if ($toStatus === 'completed' && ! $order->completed_at) {
            $payload['completed_at'] = now();
        }

        $order->update($payload);

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

            event(new OrderStatusChanged($order->fresh(), $fromStatus, $toStatus));
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

        $this->mailAdmins(new \App\Mail\AdminNewOrderMail($order));
    }

    public function notifyAdminPaymentSubmitted(Order $order): void
    {
        AdminNotification::notify(
            'payment_submitted',
            'Konfirmasi Pembayaran #'.$order->order_number,
            'Pembeli mengajukan konfirmasi transfer.',
            ['order_id' => $order->id, 'order_number' => $order->order_number],
        );

        $this->mailAdmins(new \App\Mail\AdminPaymentSubmittedMail($order));
    }

    private function mailAdmins(object $mailable): void
    {
        $emails = User::where('is_admin', true)->pluck('email')->filter()->all();

        if (empty($emails)) {
            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->queue($mailable);
        }
    }
}
