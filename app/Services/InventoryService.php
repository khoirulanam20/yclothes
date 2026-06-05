<?php

namespace App\Services;

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
        if (! $this->tracksStock($product, $variant)) {
            return true;
        }

        if ($this->allowsBackorder($product, $variant)) {
            return true;
        }

        return $this->getAvailableStock($product, $variant) >= $qty;
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

    public function decrementOnPaid(Order $order): void
    {
        if ($order->inventory_decremented) {
            return;
        }

        $order->load('items.product', 'items.variant');

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

            if ($variant) {
                $query->where('product_variant_id', $variant->id);
            } else {
                $query->where('product_id', $item->product_id)
                    ->whereNull('product_variant_id');
            }

            $inventories = $query->orderByDesc('stock')->get();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($inventory->stock, $remaining);
                if ($deduct > 0) {
                    $inventory->decrement('stock', $deduct);
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $inventory->warehouse_id,
                        'product_variant_id' => $variant?->id,
                        'type' => 'out',
                        'quantity' => $deduct,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'reason' => 'Pesanan dibayar',
                    ]);
                }
                $remaining -= $deduct;
            }

            if ($variant && $remaining > 0 && $variant->stock > 0) {
                $deduct = min($variant->stock, $remaining);
                $variant->decrement('stock', $deduct);
            }
        }

        $order->update(['inventory_decremented' => true]);
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
        $warehouse ??= Warehouse::where('is_active', true)->orderBy('id')->first();

        return Inventory::firstOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse?->id,
                'product_variant_id' => $variantId,
            ],
            ['stock' => 0, 'low_stock_threshold' => (int) setting('low_stock_threshold', 5)],
        );
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
            $allowsBackorder = $variant ? $variant->allow_backorder : $product->allow_backorder;
            $name = $variant?->name ?? $product->name;

            if ($qty > $stock && $allowsBackorder) {
                $notes[] = "{$name}: stok tersedia {$stock}, sisanya backorder.";
            }
        }

        return $notes;
    }

    private function tracksStock(Product $product, ?ProductVariant $variant): bool
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
