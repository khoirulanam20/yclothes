<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.id' => 'required|integer|exists:product_variants,id',
            'variants.*.sku' => 'required|string|max:100',
            'variants.*.price' => 'nullable|integer|min:0',
            'variants.*.is_active' => 'nullable|boolean',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'variants.*.remove_image' => 'nullable|boolean',
            'variants.*.inventories' => 'nullable|array',
            'variants.*.inventories.*.warehouse_id' => 'required|integer|exists:warehouses,id',
            'variants.*.inventories.*.stock' => 'required|integer|min:0',
            'variants.*.inventories.*.low_stock_threshold' => 'nullable|integer|min:0',
            'variants.*.inventories_json' => 'nullable|string',
        ]);

        foreach ($validated['variants'] as $index => $row) {
            $variant = ProductVariant::where('id', $row['id'])
                ->where('parent_product_id', $product->id)
                ->firstOrFail();

            if ($variant->sku !== $row['sku']) {
                $request->validate([
                    "variants.{$index}.sku" => 'unique:product_variants,sku,'.$variant->id,
                ]);
            }

            $data = [
                'sku' => $row['sku'],
                'price' => $row['price'] ?? null,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];

            $file = $request->file("variants.{$index}.image");
            if ($file) {
                if ($variant->image && ! Str::startsWith($variant->image, 'http')) {
                    Storage::disk('public')->delete($variant->image);
                }
                $data['image'] = $file->store('products/variants', 'public');
            } elseif ($request->boolean("variants.{$index}.remove_image")) {
                if ($variant->image && ! Str::startsWith($variant->image, 'http')) {
                    Storage::disk('public')->delete($variant->image);
                }
                $data['image'] = null;
            }

            $variant->update($data);

            $inventories = $this->resolveVariantInventories($row);
            if ($inventories !== null) {
                $this->inventoryService->syncInventories(
                    $product,
                    $variant->id,
                    $inventories,
                );
            }
        }

        return back()->with('success', 'Varian berhasil diperbarui');
    }

    /** @return list<array{warehouse_id: int, stock: int, low_stock_threshold?: int|null}>|null */
    private function resolveVariantInventories(array $row): ?array
    {
        if (isset($row['inventories']) && is_array($row['inventories'])) {
            return $row['inventories'];
        }

        if (! empty($row['inventories_json'])) {
            $decoded = json_decode($row['inventories_json'], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
