<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\EmailNotificationService;
use App\Services\OrderWorkflowService;
use Illuminate\Console\Command;

class ExpirePendingOrdersCommand extends Command
{
    protected $signature = 'orders:expire-pending';

    protected $description = 'Batalkan pesanan pending yang melewati batas waktu pembayaran';

    public function handle(OrderWorkflowService $workflow, EmailNotificationService $emailNotifications): int
    {
        if (! setting_bool('auto_cancel_unpaid_orders', true)) {
            $this->info('Auto-cancel unpaid orders is disabled.');

            return self::SUCCESS;
        }

        $orders = Order::where('payment_status', 'pending')
            ->where('order_status', 'pending')
            ->where('payment_method', '!=', 'cod')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->get();

        foreach ($orders as $order) {
            $notify = $emailNotifications->shouldSendPaymentExpiredEmail();
            $workflow->transition($order, 'cancelled', 'Pembayaran kedaluwarsa', 'system', null, $notify, [
                'payment_status' => 'expired',
            ]);
        }

        $this->info("Expired {$orders->count()} order(s).");

        return self::SUCCESS;
    }
}
