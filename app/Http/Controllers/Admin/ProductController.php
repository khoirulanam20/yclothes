<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Services\CategoryTreeService;
use App\Services\ProductAttributeService;
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
    ) {}

    public function index()
    {
        $products = Product::with(['category', 'attributeFamily'])->latest()->paginate(10);

        return Inertia::render('Admin/Products/Index', [
            'products' => ModelSerializer::paginated($products, [ModelSerializer::class, 'adminProduct']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Form', [
            'categoryOptions' => $this->categoryTree->formOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $familyId = $request->input('attribute_family_id');
        $validated = $request->validate(array_merge([
            'category_id' => 'required|exists:categories,id',
            'attribute_family_id' => 'nullable|exists:attribute_families,id',
            'type' => ['nullable', Rule::enum(ProductType::class)],
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products',
            'description' => 'nullable',
            'price' => 'required|integer|min:0',
            'sale_price' => 'nullable|integer|min:0|lt:price',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'badge' => 'nullable|max:50',
            'weight' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'allow_backorder' => 'nullable|boolean',
            'sizes' => 'nullable|string',
            'colors' => 'nullable|string',
            'related_products' => 'nullable|array',
            'related_products.*' => 'integer|exists:products,id',
        ], $this->attributeService->validationRules($familyId ? (int) $familyId : null)));

        $validated['image'] = $request->file('image')->store('products', 'public');
        $validated['images'] = $this->storeGalleryImages($request);
        $validated['sizes'] = $this->parseSizes($request->sizes);
        $validated['colors'] = $this->parseColors($request->colors);
        $validated['weight'] = (int) $request->weight;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['track_stock'] = $request->boolean('track_stock');
        $validated['allow_backorder'] = $request->boolean('allow_backorder');
        $validated['type'] = $request->input('type', ProductType::Simple->value);

        $product = Product::create($validated);
        $this->attributeService->syncFromRequest($product, $request);
        $this->variantService->syncFromProduct($product->fresh());
        $this->syncRelations($product, $request->input('related_products', []));

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function edit(Product $product)
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => ModelSerializer::adminProduct($product),
            'categoryOptions' => $this->categoryTree->formOptions(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $familyId = $request->input('attribute_family_id', $product->attribute_family_id);
        $validated = $request->validate(array_merge([
            'category_id' => 'required|exists:categories,id',
            'attribute_family_id' => 'nullable|exists:attribute_families,id',
            'type' => ['nullable', Rule::enum(ProductType::class)],
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products,slug,'.$product->id,
            'description' => 'nullable',
            'price' => 'required|integer|min:0',
            'sale_price' => 'nullable|integer|min:0|lt:price',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'badge' => 'nullable|max:50',
            'weight' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'allow_backorder' => 'nullable|boolean',
            'sizes' => 'nullable|string',
            'colors' => 'nullable|string',
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
            $validated['image'] = null;
        } else {
            unset($validated['image']);
        }

        if ($request->hasFile('images')) {
            $this->deleteGalleryImages($product);
            $validated['images'] = $this->storeGalleryImages($request);
        } else {
            unset($validated['images']);
        }

        $validated['sizes'] = $this->parseSizes($request->sizes);
        $validated['colors'] = $this->parseColors($request->colors);
        $validated['weight'] = (int) $request->weight;
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['track_stock'] = $request->boolean('track_stock');
        $validated['allow_backorder'] = $request->boolean('allow_backorder');
        $validated['type'] = $request->input('type', $product->type?->value ?? ProductType::Simple->value);

        $product->update($validated);
        $this->attributeService->syncFromRequest($product, $request);
        $this->variantService->syncFromProduct($product->fresh());
        $this->syncRelations($product, $request->input('related_products', []));

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diubah');
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

    private function storeGalleryImages(Request $request): ?array
    {
        if (! $request->hasFile('images')) {
            return null;
        }

        return collect($request->file('images'))->map(
            fn ($file) => $file->store('products/gallery', 'public')
        )->toArray();
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

    private function parseSizes(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function parseColors(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        return array_values(array_filter(array_map(function ($line) {
            $line = trim($line);
            if (! $line) {
                return null;
            }
            $parts = explode('|', $line, 2);
            $hex = trim($parts[0]);
            $name = isset($parts[1]) ? trim($parts[1]) : $hex;

            return $hex ? ['hex' => $hex, 'name' => $name] : null;
        }, preg_split('/\r\n|\r|\n/', $raw))));
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
