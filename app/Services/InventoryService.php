<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

class InventoryService
{
    public function canOrder(Product $product, ?ProductVariant $variant, int $qty): bool
    {
        if ($variant) {
            if (! $this->tracksStock($product, $variant)) {
                return true;
            }

            if ($this->effectiveAllowBackorder($product, $variant)) {
                return true;
            }

            return $this->getAvailableStock($product, $variant) >= $qty;
        }

        if ($product->isConfigurable()) {
            $variants = $product->relationLoaded('activeVariants')
                ? $product->activeVariants
                : $product->activeVariants()->get();

            foreach ($variants as $activeVariant) {
                if ($this->canOrder($product, $activeVariant, $qty)) {
                    return true;
                }
            }

            return false;
        }

        if (! $this->tracksStock($product, $variant)) {
            return true;
        }

        if ($this->effectiveAllowBackorder($product, $variant)) {
            return true;
        }

        return $this->getAvailableStock($product, $variant) >= $qty;
    }

    /**
     * @param  array<int, array{product: Product, variant: ProductVariant|null, qty: int, product_name?: string}>  $lines
     */
    public function assertStockAvailableWithLock(array $lines, ?int $warehouseId = null): void
    {
        foreach ($lines as $line) {
            $product = $line['product'];
            $variant = $line['variant'] ?? null;
            $qty = $line['qty'];

            if (! $this->tracksStock($product, $variant)) {
                continue;
            }

            if ($this->effectiveAllowBackorder($product, $variant)) {
                continue;
            }

            if ($warehouseId !== null) {
                $this->lockInventoriesForAtWarehouse($product, $variant, $warehouseId);

                if (! $this->canOrderAtWarehouse($product, $variant, $qty, $warehouseId)) {
                    throw new InsufficientStockException(
                        $line['product_name'] ?? $product->name,
                    );
                }

                continue;
            }

            $this->lockInventoriesFor($product, $variant);

            if (! $this->canOrder($product, $variant, $qty)) {
                throw new InsufficientStockException(
                    $line['product_name'] ?? $product->name,
                );
            }
        }
    }

    public function canOrderAtWarehouse(
        Product $product,
        ?ProductVariant $variant,
        int $qty,
        int $warehouseId,
    ): bool {
        if ($variant) {
            if (! $this->tracksStock($product, $variant)) {
                return true;
            }

            if ($this->effectiveAllowBackorder($product, $variant)) {
                return true;
            }

            return $this->getAvailableStockAtWarehouse($product, $variant, $warehouseId) >= $qty;
        }

        if ($product->isConfigurable()) {
            $variants = $product->relationLoaded('activeVariants')
                ? $product->activeVariants
                : $product->activeVariants()->get();

            foreach ($variants as $activeVariant) {
                if ($this->canOrderAtWarehouse($product, $activeVariant, $qty, $warehouseId)) {
                    return true;
                }
            }

            return false;
        }

        if (! $this->tracksStock($product, $variant)) {
            return true;
        }

        if ($this->effectiveAllowBackorder($product, $variant)) {
            return true;
        }

        return $this->getAvailableStockAtWarehouse($product, $variant, $warehouseId) >= $qty;
    }

    public function getAvailableStockAtWarehouse(
        Product $product,
        ?ProductVariant $variant,
        int $warehouseId,
    ): int {
        $query = Inventory::query()->where('warehouse_id', $warehouseId);

        if ($variant) {
            $stock = (int) (clone $query)->where('product_variant_id', $variant->id)->sum('stock');
            if ($stock > 0 || (clone $query)->where('product_variant_id', $variant->id)->exists()) {
                return $stock;
            }

            return $variant->stock;
        }

        $stock = (int) (clone $query)
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->sum('stock');

        if ($stock > 0 || (clone $query)
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->exists()) {
            return $stock;
        }

        return 0;
    }

    public function reserveForOrder(Order $order, string $reason = 'Reservasi checkout'): void
    {
        $this->decrementForOrder($order, $reason);
    }

    public function releaseForOrder(Order $order, string $reason = 'Pesanan dibatalkan'): void
    {
        if (! $order->inventory_decremented) {
            return;
        }

        $movements = StockMovement::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'out')
            ->get();

        foreach ($movements as $movement) {
            if ($movement->warehouse_id) {
                Inventory::query()
                    ->where('product_id', $movement->product_id)
                    ->where('warehouse_id', $movement->warehouse_id)
                    ->when(
                        $movement->product_variant_id,
                        fn ($query) => $query->where('product_variant_id', $movement->product_variant_id),
                        fn ($query) => $query->whereNull('product_variant_id'),
                    )
                    ->increment('stock', $movement->quantity);
            }

            StockMovement::create([
                'product_id' => $movement->product_id,
                'warehouse_id' => $movement->warehouse_id,
                'product_variant_id' => $movement->product_variant_id,
                'type' => 'in',
                'quantity' => $movement->quantity,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);
        }

        $order->updateTrusted(['inventory_decremented' => false]);
    }

    private function lockInventoriesFor(Product $product, ?ProductVariant $variant): void
    {
        $query = Inventory::query()->lockForUpdate();

        if ($variant) {
            $query->where('product_variant_id', $variant->id);
        } else {
            $query->where('product_id', $product->id)
                ->whereNull('product_variant_id');
        }

        $query->get();
    }

    private function lockInventoriesForAtWarehouse(
        Product $product,
        ?ProductVariant $variant,
        int $warehouseId,
    ): void {
        $query = Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate();

        if ($variant) {
            $query->where('product_variant_id', $variant->id);
        } else {
            $query->where('product_id', $product->id)
                ->whereNull('product_variant_id');
        }

        $query->get();
    }

    public function outOfStockBehavior(): string
    {
        return (string) setting('out_of_stock_behavior', 'show_label');
    }

    public function effectiveAllowBackorder(Product $product, ?ProductVariant $variant = null): bool
    {
        if ($this->allowsBackorder($product, $variant)) {
            return true;
        }

        return $this->outOfStockBehavior() === 'allow_backorder';
    }

    public function isOutOfStock(Product $product, ?ProductVariant $variant = null): bool
    {
        if ($variant) {
            if (! $this->tracksStock($product, $variant)) {
                return false;
            }

            return $this->getAvailableStock($product, $variant) <= 0
                && ! $this->effectiveAllowBackorder($product, $variant);
        }

        if ($product->isConfigurable()) {
            $variants = $product->relationLoaded('activeVariants')
                ? $product->activeVariants
                : $product->activeVariants()->get();

            if ($variants->isEmpty()) {
                return false;
            }

            foreach ($variants as $activeVariant) {
                if (! $this->tracksStock($product, $activeVariant)) {
                    return false;
                }

                if ($this->getAvailableStock($product, $activeVariant) > 0 || $this->effectiveAllowBackorder($product, $activeVariant)) {
                    return false;
                }
            }

            return true;
        }

        if (! $this->tracksStock($product, $variant)) {
            return false;
        }

        return $this->getAvailableStock($product, $variant) <= 0
            && ! $this->effectiveAllowBackorder($product, $variant);
    }

    public function shouldHideFromCatalog(Product $product): bool
    {
        if ($this->outOfStockBehavior() !== 'hide') {
            return false;
        }

        if ($product->isConfigurable()) {
            $variants = $product->relationLoaded('activeVariants')
                ? $product->activeVariants
                : $product->activeVariants()->get();

            if ($variants->isEmpty()) {
                return false;
            }

            foreach ($variants as $variant) {
                if (! $this->tracksStock($product, $variant)) {
                    return false;
                }

                if ($this->getAvailableStock($product, $variant) > 0 || $this->effectiveAllowBackorder($product, $variant)) {
                    return false;
                }
            }

            return true;
        }

        if (! $product->track_stock) {
            return false;
        }

        return $this->getAvailableStock($product) <= 0 && ! $this->effectiveAllowBackorder($product);
    }

    public function getAvailableStock(Product $product, ?ProductVariant $variant = null): int
    {
        if ($variant) {
            if ($variant->inventories()->exists()) {
                return (int) $variant->inventories()->sum('stock');
            }

            return $variant->stock;
        }

        if ($product->inventories()->whereNull('product_variant_id')->exists()) {
            return (int) $product->inventories()->whereNull('product_variant_id')->sum('stock');
        }

        return 0;
    }

    public function decrementForOrder(Order $order, string $reason = 'Pesanan selesai'): void
    {
        if ($order->inventory_decremented) {
            return;
        }

        $order->load('items.product', 'items.variant');
        $warehouseId = $order->warehouse_id;

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $variant = $item->variant;
            if (! $this->tracksStock($item->product, $variant)) {
                continue;
            }

            $remaining = $item->qty;
            $query = Inventory::query();

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            if ($variant) {
                $query->where('product_variant_id', $variant->id);
            } else {
                $query->where('product_id', $item->product_id)
                    ->whereNull('product_variant_id');
            }

            $inventories = $warehouseId
                ? $query->get()
                : $query->orderByDesc('stock')->get();

            if ($inventories->isEmpty()) {
                $warehouse = $warehouseId
                    ? Warehouse::query()->find($warehouseId)
                    : null;
                $inventories = collect([
                    $this->getOrCreateInventory($item->product, $warehouse, $variant?->id),
                ]);
            }

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($inventory->stock, $remaining);
                if ($deduct > 0) {
                    $inventory->decrement('stock', $deduct);
                    $this->recordOrderMovement(
                        $order,
                        $item->product_id,
                        $inventory->warehouse_id,
                        $variant?->id,
                        $deduct,
                        $reason,
                    );
                }
                $remaining -= $deduct;
            }

            if (! $warehouseId && $variant && $remaining > 0 && $variant->stock > 0) {
                $deduct = min($variant->stock, $remaining);
                $variant->decrement('stock', $deduct);
                $defaultWarehouse = Warehouse::where('is_active', true)->orderBy('id')->first();
                $this->recordOrderMovement(
                    $order,
                    $item->product_id,
                    $defaultWarehouse?->id,
                    $variant->id,
                    $deduct,
                    $reason,
                );
            }
        }

        $order->updateTrusted(['inventory_decremented' => true]);
    }

    /** @deprecated Use decrementForOrder() */
    public function decrementOnPaid(Order $order): void
    {
        $this->decrementForOrder($order, 'Pesanan dibayar');
    }

    private function recordOrderMovement(
        Order $order,
        int $productId,
        ?int $warehouseId,
        ?int $variantId,
        int $quantity,
        string $reason,
    ): void {
        StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'product_variant_id' => $variantId,
            'type' => 'out',
            'quantity' => $quantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, Inventory>
     */
    public function lowStockItems(): Collection
    {
        $globalThreshold = (int) setting('low_stock_threshold', 5);

        return Inventory::with(['product', 'warehouse'])
            ->whereHas('product')
            ->get()
            ->filter(function (Inventory $inv) use ($globalThreshold) {
                $threshold = $inv->low_stock_threshold ?: $globalThreshold;

                return $inv->stock > 0 && $inv->stock <= $threshold;
            })
            ->values();
    }

    public function getOrCreateInventory(Product $product, ?Warehouse $warehouse = null, ?int $variantId = null): Inventory
    {
        $warehouse ??= $this->defaultWarehouse();

        return Inventory::firstOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse?->id,
                'product_variant_id' => $variantId,
            ],
            ['stock' => 0, 'low_stock_threshold' => (int) setting('low_stock_threshold', 5)],
        );
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return Warehouse::where('is_active', true)->orderBy('id')->first();
    }

    /**
     * @return list<array{warehouseId: int, warehouseName: string, stock: int, lowStockThreshold: int}>
     */
    public function inventoryRowsFor(Product $product, ?int $variantId = null, ?Collection $warehouses = null): array
    {
        $warehouses ??= Warehouse::where('is_active', true)->orderBy('name')->get();
        $existing = Inventory::where('product_id', $product->id)
            ->when(
                $variantId,
                fn ($q) => $q->where('product_variant_id', $variantId),
                fn ($q) => $q->whereNull('product_variant_id'),
            )
            ->get()
            ->keyBy('warehouse_id');

        return $warehouses->map(function (Warehouse $warehouse) use ($existing) {
            $inv = $existing->get($warehouse->id);

            return [
                'warehouseId' => $warehouse->id,
                'warehouseName' => $warehouse->name,
                'stock' => $inv?->stock ?? 0,
                'lowStockThreshold' => $inv?->low_stock_threshold ?? (int) setting('low_stock_threshold', 5),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, list<array{warehouseId: int, warehouseName: string, stock: int, lowStockThreshold: int}>>
     */
    public function inventoriesGroupedByVariant(Product $product): array
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $grouped = [];

        foreach ($product->variants as $variant) {
            $grouped[$variant->id] = $this->inventoryRowsFor($product, $variant->id, $warehouses);
        }

        return $grouped;
    }

    /**
     * @param  list<array{warehouse_id: int, stock: int, low_stock_threshold?: int|null}>  $rows
     */
    public function syncInventories(Product $product, ?int $variantId, array $rows, string $reason = 'Update stok dari form produk'): void
    {
        foreach ($rows as $row) {
            $warehouseId = (int) $row['warehouse_id'];
            $targetStock = (int) ($row['stock'] ?? 0);
            $threshold = isset($row['low_stock_threshold']) && $row['low_stock_threshold'] !== ''
                ? (int) $row['low_stock_threshold']
                : (int) setting('low_stock_threshold', 5);

            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                ],
                ['stock' => 0, 'low_stock_threshold' => $threshold],
            );

            if ($inventory->stock !== $targetStock) {
                $this->adjust(
                    $product,
                    $targetStock,
                    $reason,
                    $warehouseId,
                    $variantId,
                );
            } elseif ($inventory->low_stock_threshold !== $threshold) {
                $inventory->update(['low_stock_threshold' => $threshold]);
            }
        }
    }

    public function adjust(Product $product, int $newStock, string $reason, ?int $warehouseId = null, ?int $variantId = null): StockMovement
    {
        $inventory = $this->getOrCreateInventory(
            $product,
            $warehouseId ? Warehouse::find($warehouseId) : null,
            $variantId,
        );
        $diff = $newStock - $inventory->stock;
        $inventory->update(['stock' => $newStock]);

        return StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $inventory->warehouse_id,
            'product_variant_id' => $variantId,
            'type' => 'adjustment',
            'quantity' => $diff,
            'reason' => $reason,
        ]);
    }

    public function transfer(
        Product $product,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?string $reason = null,
        ?int $variantId = null,
    ): void {
        $from = Inventory::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $fromWarehouseId, 'product_variant_id' => $variantId],
            ['stock' => 0],
        );
        $to = Inventory::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $toWarehouseId, 'product_variant_id' => $variantId],
            ['stock' => 0],
        );

        $from->decrement('stock', $quantity);
        $to->increment('stock', $quantity);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $fromWarehouseId,
            'product_variant_id' => $variantId,
            'type' => 'transfer',
            'quantity' => -$quantity,
            'reason' => $reason ?? "Transfer ke gudang #{$toWarehouseId}",
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $toWarehouseId,
            'product_variant_id' => $variantId,
            'type' => 'transfer',
            'quantity' => $quantity,
            'reason' => $reason ?? "Transfer dari gudang #{$fromWarehouseId}",
        ]);
    }

    /**
     * @param  array<int, array{product?: Product, id?: int, qty?: int}>  $cartItems
     * @return list<string>
     */
    public function hasBackorderItems(array $cartItems): array
    {
        $notes = [];

        foreach ($cartItems as $item) {
            $product = $item['product'] ?? Product::find($item['id'] ?? null);
            $variant = $item['variant'] ?? null;
            if (! $product) {
                continue;
            }

            $tracks = $variant ? $variant->track_stock : $product->track_stock;
            if (! $tracks) {
                continue;
            }

            $stock = $this->getAvailableStock($product, $variant);
            $qty = $item['qty'] ?? 1;
            $allowsBackorder = $this->effectiveAllowBackorder($product, $variant);
            $name = $variant?->name ?? $product->name;

            if ($qty > $stock && $allowsBackorder) {
                $notes[] = "{$name}: stok tersedia {$stock}, sisanya backorder.";
            }
        }

        return $notes;
    }

    public function tracksStock(Product $product, ?ProductVariant $variant): bool
    {
        if ($variant) {
            return $variant->track_stock;
        }

        return $product->track_stock;
    }

    private function allowsBackorder(Product $product, ?ProductVariant $variant): bool
    {
        if ($variant) {
            return $variant->allow_backorder;
        }

        return $product->allow_backorder;
    }
}
