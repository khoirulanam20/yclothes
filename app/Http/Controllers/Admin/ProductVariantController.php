<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductImageService;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private ProductImageService $imageService,
    ) {}

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
            'variants.*.existing_images' => 'nullable|array',
            'variants.*.existing_images.*' => 'string',
            'variants.*.new_images' => 'nullable|array',
            'variants.*.new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'variants.*.remove_images' => 'nullable|array',
            'variants.*.remove_images.*' => 'string',
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

            $requestIndex = $this->findVariantRequestIndex($request, (int) $row['id']);

            $data = [
                'sku' => $row['sku'],
                'price' => $row['price'] ?? null,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];

            $data = array_merge($data, $this->resolveVariantImages($request, $requestIndex, $variant));

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

    /** @return array{image?: ?string, images?: ?array<int, string>} */
    private function resolveVariantImages(Request $request, int $index, ProductVariant $variant): array
    {
        if ($request->boolean("variants.{$index}.remove_image")) {
            $this->deleteVariantImages($variant);

            return ['image' => null, 'images' => null];
        }

        $hasNewFiles = $this->variantUploadedFiles($request, $index) !== [];
        $removePaths = $request->input("variants.{$index}.remove_images", []);
        $hasRemovePaths = is_array($removePaths) && $removePaths !== [];
        $hasExistingInput = $request->has("variants.{$index}.existing_images");

        if (! $hasNewFiles && ! $hasRemovePaths && ! $hasExistingInput && $variant->resolved_image_paths !== []) {
            return [];
        }

        $existing = $request->input("variants.{$index}.existing_images");
        if (! is_array($existing)) {
            $existing = $variant->images ?? [];
            if ($variant->images === null && $variant->image) {
                $existing = [$variant->image];
            }
        }

        $images = $this->imageService->mergeGallery(
            $existing,
            is_array($removePaths) ? $removePaths : [],
            $this->variantUploadedFiles($request, $index),
            'products/variants/gallery',
        );

        $legacyFile = $request->file("variants.{$index}.image");
        if ($legacyFile) {
            $this->imageService->deletePath($variant->image);
            array_unshift($images, $legacyFile->store('products/variants', 'public'));
            $images = array_values(array_unique($images));
        }

        if ($images === []) {
            if ($hasRemovePaths || $hasExistingInput) {
                return ['image' => null, 'images' => null];
            }

            return [];
        }

        return [
            'images' => $images,
            'image' => $images[0],
        ];
    }

    private function deleteVariantImages(ProductVariant $variant): void
    {
        $paths = $variant->resolved_image_paths;

        foreach ($paths as $path) {
            $this->imageService->deletePath($path);
        }
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

    private function findVariantRequestIndex(Request $request, int $variantId): int
    {
        foreach ($request->input('variants', []) as $index => $variant) {
            if ((int) ($variant['id'] ?? 0) === $variantId) {
                return (int) $index;
            }
        }

        return 0;
    }

    /** @return list<UploadedFile> */
    private function variantUploadedFiles(Request $request, int $index): array
    {
        $files = $request->file("variants.{$index}.new_images");

        if ($files === null) {
            return [];
        }

        if ($files instanceof \Illuminate\Http\UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return collect($files)
            ->flatten()
            ->filter(fn ($file) => $file instanceof \Illuminate\Http\UploadedFile)
            ->values()
            ->all();
    }
}
