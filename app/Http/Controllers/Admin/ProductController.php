<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BadgePreset;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\Warehouse;
use App\Services\CategoryTreeService;
use App\Services\InventoryService;
use App\Services\ProductAttributeService;
use App\Services\ProductDuplicateService;
use App\Services\ProductVariantService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        private ProductAttributeService $attributeService,
        private ProductVariantService $variantService,
        private CategoryTreeService $categoryTree,
        private ProductDuplicateService $duplicateService,
        private InventoryService $inventoryService,
    ) {}

    public function index()
    {
        $products = Product::with(['category', 'attributeFamily'])->latest()->paginate(10);

        return Inertia::render('Admin/Products/Index', [
            'products' => ModelSerializer::paginated($products, [ModelSerializer::class, 'adminProductListItem']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'attributeFamilyOptions' => $this->attributeFamilyOptions(),
            'productTypes' => $this->productTypeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(ProductType::class)],
            'attribute_family_id' => 'required|exists:attribute_families,id',
            'sku' => 'required|string|max:100|unique:products,sku',
            'name' => 'required|max:255',
        ]);

        $category = Category::query()->orderBy('id')->first();
        if (! $category) {
            return back()->withErrors(['category_id' => 'Buat kategori terlebih dahulu.']);
        }

        $product = Product::create([
            'category_id' => $category->id,
            'attribute_family_id' => $validated['attribute_family_id'],
            'type' => $validated['type'],
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'price' => 0,
            'image' => '',
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Produk draft dibuat. Lengkapi detail produk.');
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'attributeFamily', 'attributeValues.attribute', 'variants', 'relations']);

        $familyId = $product->attribute_family_id;

        return Inertia::render('Admin/Products/Edit', [
            'product' => ModelSerializer::adminProduct($product),
            'categoryOptions' => $this->categoryTree->formOptions(),
            'attributeFamilyOptions' => $this->attributeFamilyOptions(),
            'attributeDefinitions' => $this->attributeService->definitionsForFamily($familyId),
            'attributeValues' => $this->attributeService->valuesForProduct($product),
            'productTypes' => $this->productTypeOptions(),
            'badgePresets' => BadgePreset::options(),
            'warehouses' => $this->warehouseOptions(),
            'inventoryRows' => $this->inventoryService->inventoryRowsFor($product),
            'configurableWarning' => $product->isConfigurable()
                && ! $this->familyHasVariantAxes($familyId)
                ? 'Produk configurable membutuhkan atribut size dan/atau color di keluarga atribut.'
                : null,
            'weightUnitLabel' => weight_unit_label(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $familyId = $request->input('attribute_family_id', $product->attribute_family_id);
        $validated = $request->validate(array_merge([
            'category_id' => 'required|exists:categories,id',
            'attribute_family_id' => 'required|exists:attribute_families,id',
            'type' => ['required', Rule::enum(ProductType::class)],
            'sku' => 'required|string|max:100|unique:products,sku,'.$product->id,
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products,slug,'.$product->id,
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'sale_price' => 'nullable|integer|min:0|lt:price',
            'sale_price_starts_at' => 'nullable|date',
            'sale_price_ends_at' => 'nullable|date|after_or_equal:sale_price_starts_at',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'existing_images' => 'nullable|array',
            'existing_images.*' => 'string',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'badge' => 'nullable|max:50|required_if:badge_preset,custom',
            'badge_preset' => ['nullable', Rule::enum(BadgePreset::class)],
            'badge_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'inventories' => 'nullable|array',
            'inventories.*.warehouse_id' => 'required|integer|exists:warehouses,id',
            'inventories.*.stock' => 'required|integer|min:0',
            'inventories.*.low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'allow_backorder' => 'nullable|boolean',
            'is_returnable' => 'nullable|boolean',
            'return_window_days' => 'nullable|integer|min:0',
            'warranty_days' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|max:255',
            'related_products' => 'nullable|array',
            'related_products.*' => 'integer|exists:products,id',
        ], $this->attributeService->validationRules($familyId ? (int) $familyId : null)));

        if ($request->hasFile('image')) {
            if ($product->image && ! Str::startsWith($product->image, 'http')) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($product->image && ! Str::startsWith($product->image, 'http')) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = '';
        } else {
            unset($validated['image']);
        }

        $validated['images'] = $this->mergeGalleryImages($request, $product);
        $validated['weight'] = (int) ($request->weight ?? 0);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['track_stock'] = $request->boolean('track_stock');
        $validated['allow_backorder'] = $request->boolean('allow_backorder');
        $validated['is_returnable'] = $request->boolean('is_returnable');
        $validated['return_window_days'] = $request->input('return_window_days');
        $validated['warranty_days'] = $request->input('warranty_days');
        $validated['sale_price_starts_at'] = $request->input('sale_price_starts_at') ?: null;
        $validated['sale_price_ends_at'] = $request->input('sale_price_ends_at') ?: null;

        $this->normalizeBadgeFields($validated);

        unset($validated['existing_images'], $validated['new_images'], $validated['remove_images'], $validated['inventories']);

        $product->update($validated);
        $this->attributeService->syncFromRequest($product, $request);
        $this->attributeService->syncLegacyVariantColumns($product->fresh());
        $this->variantService->syncFromProduct($product->fresh());
        $this->syncRelations($product, $request->input('related_products', []));

        if (
            $product->type === ProductType::Simple
            && $request->boolean('track_stock')
            && $request->has('inventories')
        ) {
            $this->inventoryService->syncInventories(
                $product,
                null,
                $request->input('inventories', []),
            );
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Produk berhasil disimpan');
    }

    public function duplicate(Product $product)
    {
        $copy = $this->duplicateService->duplicate($product);

        return redirect()
            ->route('admin.products.edit', $copy)
            ->with('success', 'Produk berhasil diduplikasi sebagai draft.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && ! Str::startsWith($product->image, 'http')) {
            Storage::disk('public')->delete($product->image);
        }
        $this->deleteGalleryImages($product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus');
    }

    private function attributeFamilyOptions(): array
    {
        return AttributeFamily::orderBy('name')->get()->map(fn ($f) => [
            'id' => $f->id,
            'name' => $f->name,
        ])->values()->all();
    }

    private function productTypeOptions(): array
    {
        return collect(ProductType::cases())->map(fn (ProductType $type) => [
            'value' => $type->value,
            'label' => match ($type) {
                ProductType::Simple => 'Barang Tunggal',
                ProductType::Configurable => 'Barang dengan Varian',
            },
        ])->values()->all();
    }

    private function familyHasVariantAxes(?int $familyId): bool
    {
        if (! $familyId) {
            return false;
        }

        $codes = $this->attributeService->familyAttributes($familyId)->pluck('code');

        return $codes->contains('size') || $codes->contains('color');
    }

    private function mergeGalleryImages(Request $request, Product $product): array
    {
        $existing = $request->input('existing_images', $product->images ?? []);
        $remove = $request->input('remove_images', []);

        $kept = collect($existing)
            ->filter(fn ($path) => $path && ! in_array($path, $remove, true))
            ->values();

        foreach ($remove as $path) {
            if ($path && ! Str::startsWith($path, 'http') && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $file) {
                $kept->push($file->store('products/gallery', 'public'));
            }
        }

        return $kept->values()->all();
    }

    private function deleteGalleryImages(Product $product): void
    {
        if (! $product->images) {
            return;
        }
        foreach ($product->images as $path) {
            if (! Str::startsWith($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function warehouseOptions(): array
    {
        return Warehouse::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])
            ->values()
            ->all();
    }

    /** @param  array<string, mixed>  $validated */
    private function normalizeBadgeFields(array &$validated): void
    {
        $preset = BadgePreset::tryFrom($validated['badge_preset'] ?? '') ?? BadgePreset::None;
        $validated['badge_preset'] = $preset;

        if ($preset === BadgePreset::None) {
            $validated['badge'] = null;
            $validated['badge_color'] = null;

            return;
        }

        if ($preset === BadgePreset::Custom) {
            return;
        }

        $validated['badge'] = $validated['badge'] ?: $preset->defaultLabel();
        $validated['badge_color'] = $validated['badge_color'] ?: $preset->defaultColor();
    }

    private function syncRelations(Product $product, array $relatedIds): void
    {
        $product->relations()->where('type', 'related')->delete();

        foreach (array_unique($relatedIds) as $relatedId) {
            if ((int) $relatedId === $product->id) {
                continue;
            }
            ProductRelation::create([
                'product_id' => $product->id,
                'related_product_id' => $relatedId,
                'type' => 'related',
            ]);
        }
    }
}
