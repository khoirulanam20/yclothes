<?php

namespace Tests\Feature;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_faq_category_and_item(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->post('/admin/faq-categories', ['name' => 'Pengiriman', 'sort_order' => 1])
            ->assertRedirect(route('admin.faq-categories.index'));

        $category = FaqCategory::where('name', 'Pengiriman')->first();

        $this->actingAs($admin)
            ->post("/admin/faq-categories/{$category->id}/items", [
                'question' => 'Berapa lama pengiriman?',
                'answer' => '<p>2-5 hari kerja.</p>',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.faq-categories.items.index', $category));

        $this->assertDatabaseHas('faq_items', ['question' => 'Berapa lama pengiriman?']);
    }

    public function test_faq_page_shows_active_items(): void
    {
        $category = FaqCategory::create(['name' => 'Umum', 'sort_order' => 1]);
        FaqItem::create([
            'category_id' => $category->id,
            'question' => 'Apa itu YClothes?',
            'answer' => '<p>Toko fashion online.</p>',
            'is_active' => true,
        ]);

        $this->get('/faq')->assertOk()->assertSee('Apa itu YClothes?');
    }
}
