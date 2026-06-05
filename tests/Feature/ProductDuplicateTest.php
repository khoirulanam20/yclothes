<?php

namespace Tests\Feature;

use App\Models\AttributeFamily;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_duplicate_product(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $original = Product::first();
        $original->update(['sku' => 'ORIG-001', 'is_active' => true]);

        Storage::disk('public')->put($original->image, 'fake');

        $response = $this->actingAs($admin)->post(route('admin.products.duplicate', $original));

        $copy = Product::where('sku', '!=', 'ORIG-001')->where('name', 'like', 'Salinan dari%')->first();
        $this->assertNotNull($copy);
        $this->assertFalse($copy->is_active);
        $this->assertNotEquals($original->id, $copy->id);

        $response->assertRedirect(route('admin.products.edit', $copy));
    }
}
