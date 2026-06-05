<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\ModelSerializer;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::published()->latest('published_at')->paginate(9);

        return Inertia::render('Guest/Blog/Index', [
            'posts' => ModelSerializer::paginated($posts, [ModelSerializer::class, 'blogPost']),
        ]);
    }

    public function show(string $slug)
    {
        $post = BlogPost::where('slug', $slug)->firstOrFail();

        if (! $post->isPublished()) {
            $isAdminPreview = request()->user('web')?->is_admin && request()->boolean('preview');
            abort_unless($isAdminPreview, 404);
        }

        return Inertia::render('Guest/Blog/Show', [
            'post' => ModelSerializer::blogPost($post),
        ]);
    }
}
