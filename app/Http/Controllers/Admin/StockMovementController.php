<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $movements = StockMovement::with(['product', 'warehouse'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/StockMovements/Index', [
            'movements' => [
                'data' => ModelSerializer::stockMovements(collect($movements->items())),
                'links' => $movements->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $movements->currentPage(),
                    'last_page' => $movements->lastPage(),
                    'per_page' => $movements->perPage(),
                    'total' => $movements->total(),
                ],
            ],
        ]);
    }

    public function createAdjustment()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/StockMovements/Adjustment', [
            'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
            'warehouses' => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values()->all(),
        ]);
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'new_stock' => 'required|integer|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $this->inventoryService->adjust(
            $product,
            $validated['new_stock'],
            $validated['reason'],
            $validated['warehouse_id'] ?? null,
        );

        return redirect()->route('admin.stock-movements.index')
            ->with('success', 'Penyesuaian stok berhasil dicatat');
    }

    public function createTransfer()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/StockMovements/Transfer', [
            'products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()->all(),
            'warehouses' => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values()->all(),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $this->inventoryService->transfer(
            $product,
            $validated['from_warehouse_id'],
            $validated['to_warehouse_id'],
            $validated['quantity'],
            $validated['reason'] ?? null,
        );

        return redirect()->route('admin.stock-movements.index')
            ->with('success', 'Transfer stok berhasil');
    }
}
