<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ExpirePendingOrdersCommand extends Command
{
    protected $signature = 'orders:expire-pending';

    protected $description = 'Batalkan pesanan pending yang melewati batas waktu pembayaran';

    public function handle(OrderWorkflowService $workflow): int
    {
        $orders = Order::where('payment_status', 'pending')
            ->where('order_status', 'pending')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->get();

        foreach ($orders as $order) {
            $workflow->transition($order, 'cancelled', 'Pembayaran kedaluwarsa', 'system', null, true, [
                'payment_status' => 'expired',
            ]);
        }

        $this->info("Expired {$orders->count()} order(s).");

        return self::SUCCESS;
    }
}
