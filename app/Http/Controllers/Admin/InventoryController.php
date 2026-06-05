<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Support\InventoryFormOptions;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $warehouseId = $request->input('warehouse_id');

        $query = Inventory::with(['product', 'variant', 'warehouse'])
            ->whereHas('product')
            ->where(function ($builder) {
                $builder->whereNotNull('product_variant_id')
                    ->orWhereHas('product', fn ($product) => $product->where('type', ProductType::Simple));
            });

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%'.$search.'%';
                $builder->whereHas('product', function ($product) use ($like) {
                    $product->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like);
                })->orWhereHas('variant', function ($variant) use ($like) {
                    $variant->where('sku', 'like', $like)
                        ->orWhere('name', 'like', $like);
                });
            });
        }

        $inventories = $query->latest()->paginate(20)->withQueryString();

        $recentMovements = StockMovement::with(['product', 'variant', 'warehouse'])
            ->latest('created_at')
            ->limit(20)
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Inventories/Index', [
            'inventories' => [
                'data' => collect($inventories->items())->map(fn ($inv) => $this->inventory($inv))->values()->all(),
                'links' => $inventories->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $inventories->currentPage(),
                    'last_page' => $inventories->lastPage(),
                    'per_page' => $inventories->perPage(),
                    'total' => $inventories->total(),
                ],
            ],
            'recentMovements' => ModelSerializer::stockMovements($recentMovements),
            'warehouses' => $warehouses->map(fn ($warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])->values()->all(),
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId ? (string) $warehouseId : '',
            ],
        ]);
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Inventories/Form', [
            'products' => InventoryFormOptions::products(),
            'warehouses' => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values()->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInventory($request);
        Inventory::create($validated);

        return redirect()->route('admin.inventories.index')->with('success', 'Stok berhasil ditambahkan');
    }

    public function edit(Inventory $inventory)
    {
        $inventory->load(['product', 'variant', 'warehouse']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Inventories/Form', [
            'inventory' => $this->inventory($inventory),
            'products' => InventoryFormOptions::products(),
            'warehouses' => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values()->all(),
        ]);
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $this->validateInventory($request, $inventory);
        $inventory->update($validated);

        return redirect()->route('admin.inventories.index')->with('success', 'Stok berhasil diubah');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return redirect()->route('admin.inventories.index')->with('success', 'Stok berhasil dihapus');
    }

    private function inventory(Inventory $inventory): array
    {
        $variant = $inventory->relationLoaded('variant') ? $inventory->variant : null;

        return [
            'id' => $inventory->id,
            'stock' => $inventory->stock,
            'lowStockThreshold' => $inventory->low_stock_threshold,
            'productId' => $inventory->product_id,
            'warehouseId' => $inventory->warehouse_id,
            'productVariantId' => $inventory->product_variant_id,
            'product' => $inventory->relationLoaded('product') && $inventory->product
                ? [
                    'name' => $inventory->product->name,
                    'sku' => $inventory->product->sku,
                    'type' => $inventory->product->type?->value ?? $inventory->product->type,
                ]
                : null,
            'variant' => $variant
                ? [
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'label' => InventoryFormOptions::variantLabel($variant),
                ]
                : null,
            'displayName' => $this->inventoryDisplayName($inventory->product, $variant),
            'displaySku' => $variant?->sku ?? $inventory->product?->sku,
            'warehouse' => $inventory->relationLoaded('warehouse') && $inventory->warehouse
                ? ['name' => $inventory->warehouse->name]
                : null,
        ];
    }

    private function inventoryDisplayName(?Product $product, ?ProductVariant $variant): string
    {
        if (! $product) {
            return '—';
        }

        if (! $variant) {
            return $product->name;
        }

        $label = InventoryFormOptions::variantLabel($variant);

        return $label ? "{$product->name} — {$label}" : $product->name;
    }

    private function validateInventory(Request $request, ?Inventory $inventory = null): array
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'stock' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $validated['product_variant_id'] = InventoryFormOptions::resolveVariantId(
            $product,
            $validated['product_variant_id'] ?? null,
        );

        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;

        $uniqueRule = Rule::unique('inventories')
            ->where('product_id', $validated['product_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->where('product_variant_id', $validated['product_variant_id'] ?? null);

        if ($inventory) {
            $uniqueRule->ignore($inventory->id);
        }

        $request->validate(['product_id' => [$uniqueRule]]);

        return $validated;
    }
}
