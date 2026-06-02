<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('order')->get();

        return view('admin.products.form', ['product' => new Product, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products',
            'description' => 'nullable',
            'price' => 'required|integer|min:0',
            'sale_price' => 'nullable|integer|min:0|lt:price',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'sizes' => 'nullable',
            'colors' => 'nullable',
            'badge' => 'nullable|max:50',
            'weight' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
        ]);

        $validated['image'] = $request->file('image')->store('products', 'public');
        $validated['images'] = $this->storeGalleryImages($request);
        $validated['sizes'] = $request->sizes ? array_map('trim', explode(',', $request->sizes)) : null;
        $validated['colors'] = $request->colors ? array_values(array_filter(array_map(function ($line) {
            $line = trim($line);
            if (! $line) return null;
            $parts = explode('|', $line, 2);
            $hex = trim($parts[0]);
            $name = isset($parts[1]) ? trim($parts[1]) : $hex;
            return $hex ? ['hex' => $hex, 'name' => $name] : null;
        }, preg_split('/\r\n|\r|\n/', $request->colors)))) : null;
        $validated['weight'] = (int) $request->weight;
        $validated['is_featured'] = $request->boolean('is_featured');

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('order')->get();

        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:products,slug,'.$product->id,
            'description' => 'nullable',
            'price' => 'required|integer|min:0',
            'sale_price' => 'nullable|integer|min:0|lt:price',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'sizes' => 'nullable',
            'colors' => 'nullable',
            'badge' => 'nullable|max:50',
            'weight' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
        ]);

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

        $validated['sizes'] = $request->sizes ? array_map('trim', explode(',', $request->sizes)) : null;
        $validated['colors'] = $request->colors ? array_values(array_filter(array_map(function ($line) {
            $line = trim($line);
            if (! $line) return null;
            $parts = explode('|', $line, 2);
            $hex = trim($parts[0]);
            $name = isset($parts[1]) ? trim($parts[1]) : $hex;
            return $hex ? ['hex' => $hex, 'name' => $name] : null;
        }, preg_split('/\r\n|\r|\n/', $request->colors)))) : null;
        $validated['weight'] = (int) $request->weight;
        $validated['is_featured'] = $request->boolean('is_featured');

        $product->update($validated);

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
}
