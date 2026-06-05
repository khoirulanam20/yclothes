<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@yclothes.test')->firstOrFail();
    }

    private function sampleLayout(): string
    {
        return json_encode([
            'root' => ['props' => ['showBreadcrumb' => true, 'pageTitle' => 'Kebijakan Privasi']],
            'content' => [
                [
                    'type' => 'RichText',
                    'props' => [
                        'id' => 'content-1',
                        'html' => '<p>Privasi kami.</p><script>alert(1)</script>',
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_can_create_page_via_builder(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/cms-pages/builder', [
                'title' => 'Kebijakan Privasi',
                'slug' => 'kebijakan-privasi',
                'status' => 'draft',
                'meta_title' => 'Kebijakan Privasi',
                'meta_description' => 'Kebijakan privasi toko.',
                'layout_json' => $this->sampleLayout(),
            ])
            ->assertRedirect();

        $page = CmsPage::where('slug', 'kebijakan-privasi')->first();
        $this->assertNotNull($page);
        $this->assertSame('draft', $page->status);
        $this->assertNotEmpty($page->layout_json['content']);
    }

    public function test_builder_save_sanitizes_rich_text_html(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/cms-pages/builder', [
                'title' => 'Safe Page',
                'slug' => 'safe-page',
                'status' => 'published',
                'layout_json' => $this->sampleLayout(),
            ]);

        $page = CmsPage::where('slug', 'safe-page')->firstOrFail();
        $html = $page->layout_json['content'][0]['props']['html'] ?? '';

        $this->assertStringContainsString('<p>Privasi kami.</p>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_admin_can_update_page_via_builder(): void
    {
        $page = CmsPage::create([
            'title' => 'Old Title',
            'slug' => 'old-title',
            'status' => 'draft',
            'layout_json' => ['root' => ['props' => []], 'content' => []],
        ]);

        $updatedLayout = json_encode([
            'root' => ['props' => ['showBreadcrumb' => true, 'pageTitle' => 'New Title']],
            'content' => [
                ['type' => 'Heading', 'props' => ['id' => 'h1', 'text' => 'New Title', 'level' => 'h1', 'align' => 'left']],
            ],
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/cms-pages/{$page->id}/builder", [
                'title' => 'New Title',
                'slug' => 'new-title',
                'status' => 'published',
                'meta_title' => 'New Title',
                'meta_description' => 'Updated description',
                'layout_json' => $updatedLayout,
            ])
            ->assertRedirect(route('admin.cms-pages.builder', $page));

        $page->refresh();
        $this->assertSame('New Title', $page->title);
        $this->assertSame('new-title', $page->slug);
        $this->assertSame('published', $page->status);
        $this->assertSame('Heading', $page->layout_json['content'][0]['type']);
    }

    public function test_builder_auto_migrates_legacy_content(): void
    {
        $page = CmsPage::create([
            'title' => 'Legacy Page',
            'slug' => 'legacy-page',
            'content' => '<p>Legacy content</p>',
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/cms-pages/{$page->id}/builder")
            ->assertOk();

        $page->refresh();
        $this->assertNotEmpty($page->layout_json['content']);
        $this->assertSame('puck-1', $page->layout_version);
    }

    public function test_admin_can_preview_draft_page(): void
    {
        $page = CmsPage::create([
            'title' => 'Draft Preview',
            'slug' => 'draft-preview',
            'status' => 'draft',
            'layout_json' => [
                'root' => ['props' => ['pageTitle' => 'Draft Preview']],
                'content' => [
                    ['type' => 'RichText', 'props' => ['id' => 'rt-1', 'html' => '<p>Draft preview content</p>']],
                ],
            ],
        ]);

        $this->actingAs($this->admin())
            ->get("/page/{$page->slug}?preview=1")
            ->assertOk()
            ->assertSee('Draft preview content');

        $this->get("/page/{$page->slug}")->assertNotFound();
    }

    public function test_new_builder_page_is_accessible(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/cms-pages/builder/new')
            ->assertOk();
    }

    public function test_builder_rejects_unknown_block_type(): void
    {
        $layout = json_encode([
            'root' => ['props' => []],
            'content' => [
                ['type' => 'UnknownBlock', 'props' => ['id' => 'x-1']],
            ],
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/cms-pages/builder', [
                'title' => 'Invalid Block',
                'slug' => 'invalid-block',
                'status' => 'draft',
                'layout_json' => $layout,
            ])
            ->assertSessionHasErrors('layout_json');

        $this->assertDatabaseMissing('cms_pages', ['slug' => 'invalid-block']);
    }

    public function test_builder_accepts_multi_block_layout(): void
    {
        $layout = json_encode([
            'root' => ['props' => ['showBreadcrumb' => 'yes', 'pageTitle' => 'Multi']],
            'content' => [
                ['type' => 'PageBanner', 'props' => ['id' => 'b1', 'title' => 'Banner', 'subtitle' => '', 'imageUrl' => '']],
                ['type' => 'Heading', 'props' => ['id' => 'h1', 'text' => 'Judul', 'level' => 'h2', 'align' => 'left']],
                ['type' => 'Button', 'props' => ['id' => 'btn1', 'label' => 'CTA', 'href' => '/products', 'variant' => 'primary']],
            ],
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/cms-pages/builder', [
                'title' => 'Multi Block',
                'slug' => 'multi-block',
                'status' => 'published',
                'layout_json' => $layout,
            ])
            ->assertRedirect();

        $page = CmsPage::where('slug', 'multi-block')->firstOrFail();
        $this->assertCount(3, $page->layout_json['content']);
    }

    public function test_admin_can_upload_editor_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('banner.jpg');

        $response = $this->actingAs($this->admin())
            ->post('/admin/editor/upload-image', ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['path', 'url']);

        Storage::disk('public')->assertExists($response->json('path'));
    }
}
