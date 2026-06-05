<?php

namespace Tests\Feature;

use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_navigation_item(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post('/admin/navigation', [
                'menu' => 'header',
                'label' => 'Blog',
                'url' => '/blog',
                'sort_order' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.navigation.index'));

        $this->assertDatabaseHas('navigation_items', ['label' => 'Blog', 'menu' => 'header']);
    }

    public function test_navigation_helper_returns_seeded_items(): void
    {
        $items = navigation('header');

        $this->assertTrue($items->isNotEmpty());
        $this->assertTrue($items->contains('label', 'Beranda'));
    }

    public function test_homepage_renders_navigation_from_db(): void
    {
        $this->get('/')->assertOk()->assertSee('Beranda');
    }
}
