<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\HtmlSanitizer;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogPostController extends Controller
{
    public function index()
    {
        $posts = BlogPost::latest()->paginate(15);

        return Inertia::render('Admin/Blog/Index', [
            'posts' => ModelSerializer::paginated($posts, [ModelSerializer::class, 'blogPost']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Blog/Form');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePost($request);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }

        $validated['content'] = HtmlSanitizer::clean($validated['content'] ?? null);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['author'] = ! empty($validated['author']) ? $validated['author'] : Auth::user()->name;

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        BlogPost::create($validated);

        return redirect()->route('admin.blog-posts.index')->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function edit(BlogPost $blogPost)
    {
        return Inertia::render('Admin/Blog/Form', [
            'post' => ModelSerializer::blogPost($blogPost),
        ]);
    }

    public function update(Request $request, BlogPost $blogPost)
    {
        $validated = $this->validatePost($request, $blogPost->id);

        if ($request->hasFile('featured_image')) {
            if ($blogPost->featured_image) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        } elseif ($request->boolean('remove_featured_image')) {
            if ($blogPost->featured_image) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }
            $validated['featured_image'] = null;
        } else {
            unset($validated['featured_image']);
        }

        $validated['content'] = HtmlSanitizer::clean($validated['content'] ?? null);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

        if ($validated['status'] === 'published' && ! $blogPost->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $blogPost->update($validated);

        return redirect()->route('admin.blog-posts.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(BlogPost $blogPost)
    {
        if ($blogPost->featured_image) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }

        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    private function validatePost(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = 'nullable|max:255|unique:blog_posts,slug';
        if ($ignoreId) {
            $slugRule .= ','.$ignoreId;
        }

        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:1000',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_featured_image' => 'nullable|boolean',
            'author' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
        ]);
    }
}
