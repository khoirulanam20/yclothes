<?php

namespace Tests\Feature;

use App\Models\PromotionPopup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromotionPopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_create_promotion_popup(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post(route('admin.promotion-popups.store'), [
                'title' => 'Promo Ramadan',
                'image' => UploadedFile::fake()->image('popup.jpg'),
                'button_label' => 'Belanja',
                'button_url' => '/products',
                'display_duration_seconds' => 5,
                'start_date' => now()->subHour()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'show_on_pages' => ['home'],
                'is_active' => true,
                'priority' => 1,
            ])
            ->assertRedirect(route('admin.promotion-popups.index'));

        $this->assertDatabaseHas('promotion_popups', ['title' => 'Promo Ramadan', 'is_active' => true]);
    }

    public function test_active_popup_shared_on_homepage(): void
    {
        PromotionPopup::create([
            'title' => 'Popup Home',
            'image' => 'promotion-popups/test.jpg',
            'button_label' => 'Lihat',
            'button_url' => '/products',
            'display_duration_seconds' => 0,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'show_on_pages' => ['home'],
            'is_active' => true,
            'priority' => 1,
        ]);
        Storage::disk('public')->put('promotion-popups/test.jpg', 'fake');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('promotionPopup.title', 'Popup Home')
            );
    }

    public function test_popup_not_shown_when_inactive(): void
    {
        PromotionPopup::create([
            'title' => 'Popup Inactive',
            'image' => 'promotion-popups/test.jpg',
            'display_duration_seconds' => 0,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'show_on_pages' => ['home'],
            'is_active' => false,
            'priority' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('promotionPopup', null));
    }
}
