<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with(['product', 'variant', 'warehouse'])
            ->latest()
            ->paginate(20);

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
        ]);
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Inventories/Form', [
            'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
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
        $inventory->load(['product', 'warehouse']);
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Inventories/Form', [
            'inventory' => $this->inventory($inventory),
            'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
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
        return [
            'id' => $inventory->id,
            'stock' => $inventory->stock,
            'lowStockThreshold' => $inventory->low_stock_threshold,
            'productId' => $inventory->product_id,
            'warehouseId' => $inventory->warehouse_id,
            'productVariantId' => $inventory->product_variant_id,
            'product' => $inventory->relationLoaded('product') && $inventory->product
                ? ['name' => $inventory->product->name]
                : null,
            'warehouse' => $inventory->relationLoaded('warehouse') && $inventory->warehouse
                ? ['name' => $inventory->warehouse->name]
                : null,
        ];
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
