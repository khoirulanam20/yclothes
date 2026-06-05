<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_blog_post(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post('/admin/blog-posts', [
                'title' => 'Tips Fashion',
                'slug' => 'tips-fashion',
                'content' => '<p>Konten artikel.</p>',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.blog-posts.index'));

        $this->assertDatabaseHas('blog_posts', ['slug' => 'tips-fashion', 'status' => 'published']);
    }

    public function test_published_blog_post_is_accessible(): void
    {
        BlogPost::create([
            'title' => 'Test Blog',
            'slug' => 'test-blog',
            'content' => '<p>Hello Blog</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/blog/test-blog')->assertOk()->assertSee('Hello Blog');
    }

    public function test_draft_blog_post_returns_404(): void
    {
        BlogPost::create([
            'title' => 'Draft Blog',
            'slug' => 'draft-blog',
            'content' => '<p>Secret</p>',
            'status' => 'draft',
        ]);

        $this->get('/blog/draft-blog')->assertNotFound();
    }

    public function test_blog_index_lists_published_posts(): void
    {
        BlogPost::create([
            'title' => 'Artikel Satu',
            'slug' => 'artikel-satu',
            'content' => '<p>Satu</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/blog')->assertOk()->assertSee('Artikel Satu');
    }
}
