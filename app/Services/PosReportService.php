<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PosOrderPayment;
use App\Models\User;
use App\Support\Serializers\PosOrderSerializer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PosReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(User $user, string $from, string $to, string $period = 'day'): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $orders = Order::query()
            ->where('order_source', 'pos')
            ->where('order_status', '!=', 'cancelled')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with(['items', 'posPayments'])
            ->get();

        $orderCount = $orders->count();
        $totalRevenue = (int) $orders->sum('grand_total');
        $totalItems = (int) $orders->sum(fn (Order $order) => $order->items->sum('qty'));
        $discountTotal = (int) $orders->sum('discount_amount');

        $paymentIds = $orders->pluck('id');
        $payments = PosOrderPayment::query()
            ->whereIn('order_id', $paymentIds)
            ->get();

        $cashPayments = (int) $payments->where('method', 'cash')->sum('amount');
        $transferPayments = (int) $payments->where('method', 'transfer')->sum('amount');

        $sparkline = Order::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(grand_total) as revenue'))
            ->where('order_source', 'pos')
            ->where('order_status', '!=', 'cancelled')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'orders' => (int) $row->orders,
                'revenue' => (int) $row->revenue,
            ])
            ->values()
            ->all();

        return [
            'orders' => $orderCount,
            'averageOrderValue' => $orderCount > 0 ? (int) round($totalRevenue / $orderCount) : 0,
            'averageItemsPerOrder' => $orderCount > 0 ? (int) round($totalItems / $orderCount) : 0,
            'discountTotal' => $discountTotal,
            'cashPayments' => $cashPayments,
            'transferPayments' => $transferPayments,
            'sparkline' => $sparkline,
            'period' => $period,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportData(User $user, string $from, string $to, string $period = 'day'): array
    {
        $summary = $this->summary($user, $from, $to, $period);

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $orderList = Order::query()
            ->where('order_source', 'pos')
            ->where('order_status', '!=', 'cancelled')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->withSum('items as total_qty', 'qty')
            ->latest()
            ->get()
            ->map(fn (Order $order) => PosOrderSerializer::summary($order))
            ->values()
            ->all();

        return array_merge($summary, [
            'from' => $from,
            'to' => $to,
            'orderList' => $orderList,
        ]);
    }
}
