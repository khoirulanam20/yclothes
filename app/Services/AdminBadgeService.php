<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\User;

class AdminBadgeService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /** @return array{orders: int, returns: int, reviews: int, lowStock: int, notificationsUnread: int} */
    public function countsForAdmin(User $admin): array
    {
        $permissions = $this->permissions($admin);

        return [
            'orders' => $this->canSeeOrders($permissions) ? $this->ordersAwaitingActionCount() : 0,
            'returns' => $this->canSeeReturns($permissions) ? $this->returnsAwaitingActionCount() : 0,
            'reviews' => $this->canSeeReviews($permissions) ? $this->pendingReviewsCount() : 0,
            'lowStock' => $this->canSeeInventory($permissions) ? $this->lowStockCount() : 0,
            'notificationsUnread' => AdminNotification::whereNull('read_at')->count(),
        ];
    }

    public function ordersAwaitingActionCount(): int
    {
        return Order::query()
            ->whereNotIn('order_status', ['completed', 'cancelled', 'return'])
            ->where(function ($query) {
                $query->whereIn('order_status', ['confirmed', 'processed'])
                    ->orWhere(function ($sub) {
                        $sub->whereIn('order_status', ['pending', 'awaiting_verification'])
                            ->where('payment_status', '!=', 'paid')
                            ->where(function ($payment) {
                                $payment->whereIn('payment_method', ['bank_transfer', 'qris'])
                                    ->orWhereHas('paymentConfirmations', fn ($confirmations) => $confirmations->where('status', 'pending'));
                            });
                    });
            })
            ->count();
    }

    public function returnsAwaitingActionCount(): int
    {
        return ReturnRequest::query()
            ->whereIn('status', ['pending_review', 'return_in_transit', 'received'])
            ->count();
    }

    public function pendingReviewsCount(): int
    {
        return Review::query()->where('is_approved', false)->count();
    }

    public function lowStockCount(): int
    {
        return $this->inventoryService->lowStockItems()->count();
    }

    /** @return list<string> */
    private function permissions(User $admin): array
    {
        if ($admin->isSuperAdmin()) {
            return ['*'];
        }

        return $admin->adminRole?->permissions ?? [];
    }

    /** @param list<string> $permissions */
    private function hasPermission(array $permissions, string|array $required): bool
    {
        if (in_array('*', $permissions, true)) {
            return true;
        }

        $keys = is_array($required) ? $required : [$required];

        return array_intersect($permissions, $keys) !== [];
    }

    /** @param list<string> $permissions */
    private function canSeeOrders(array $permissions): bool
    {
        return $this->hasPermission($permissions, ['orders.view', 'orders.manage']);
    }

    /** @param list<string> $permissions */
    private function canSeeReturns(array $permissions): bool
    {
        return $this->hasPermission($permissions, 'orders.manage');
    }

    /** @param list<string> $permissions */
    private function canSeeReviews(array $permissions): bool
    {
        return $this->hasPermission($permissions, ['products.view', 'products.manage']);
    }

    /** @param list<string> $permissions */
    private function canSeeInventory(array $permissions): bool
    {
        return $this->hasPermission($permissions, 'inventory.manage');
    }
}
