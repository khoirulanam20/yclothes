<?php

namespace Tests\Feature;

use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SliderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_create_slider_via_homepage_builder(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post('/admin/homepage/sliders', [
                'title' => 'Promo Slider',
                'image' => UploadedFile::fake()->image('slider.jpg'),
                'link_url' => '/products',
                'sort_order' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sliders', ['title' => 'Promo Slider', 'is_active' => true]);
    }

    public function test_legacy_sliders_route_redirects_to_homepage_builder(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->get('/admin/sliders')
            ->assertRedirect('/admin/homepage');
    }

    public function test_active_slider_shows_on_homepage(): void
    {
        Slider::create([
            'title' => 'Home Slider',
            'image' => 'sliders/test.jpg',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('sliders/test.jpg', 'fake');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Home')
                ->has('sections')
                ->where('sections.0.type', 'hero_slider')
            );
    }
}
