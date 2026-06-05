<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_published_page_with_layout_is_accessible(): void
    {
        CmsPage::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'status' => 'published',
            'layout_json' => [
                'root' => ['props' => ['showBreadcrumb' => true, 'pageTitle' => 'Test Page']],
                'content' => [
                    [
                        'type' => 'RichText',
                        'props' => ['id' => 'rt-1', 'html' => '<p>Hello CMS</p>'],
                    ],
                ],
            ],
            'layout_version' => 'puck-1',
        ]);

        $this->get('/page/test-page')
            ->assertOk()
            ->assertSee('Hello CMS');
    }

    public function test_draft_page_returns_404(): void
    {
        CmsPage::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'status' => 'draft',
            'layout_json' => [
                'root' => ['props' => []],
                'content' => [
                    ['type' => 'RichText', 'props' => ['id' => 'rt-1', 'html' => '<p>Secret</p>']],
                ],
            ],
        ]);

        $this->get('/page/draft-page')->assertNotFound();
    }

    public function test_legacy_about_route_redirects(): void
    {
        $this->get('/tentang-kami')
            ->assertRedirect(route('pages.show', ['slug' => 'tentang-kami']));
    }

    public function test_seeded_about_page_renders_puck_content(): void
    {
        $this->get('/page/tentang-kami')
            ->assertOk();
    }
}
