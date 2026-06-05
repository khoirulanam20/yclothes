<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_product_update_is_logged(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $product = Product::first();

        $this->actingAs($admin)
            ->put("/admin/products/{$product->id}", [
                'name' => $product->name,
                'slug' => $product->slug,
                'category_id' => $product->category_id,
                'price' => $product->price,
                'description' => $product->description,
                'is_featured' => $product->is_featured ? 1 : 0,
            ]);

        $this->assertTrue(
            ActivityLog::where('user_id', $admin->id)
                ->where('action', 'admin.products.update')
                ->exists()
        );
    }

    public function test_activity_log_viewer_is_accessible_by_super_admin(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $this->actingAs($admin)
            ->get('/admin/activity-logs')
            ->assertOk();
    }
}
