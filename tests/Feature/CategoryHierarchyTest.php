<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CategoryTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_admin_can_create_subcategory(): void
    {
        $parent = Category::create(['name' => 'Anak', 'slug' => 'anak', 'order' => 10]);

        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Bayi',
            'slug' => 'anak-bayi',
            'parent_id' => $parent->id,
            'order' => 1,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'slug' => 'anak-bayi',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_circular_parent_is_rejected(): void
    {
        $parent = Category::create(['name' => 'Anak', 'slug' => 'anak', 'order' => 10]);
        $child = Category::create(['name' => 'Bayi', 'slug' => 'anak-bayi', 'parent_id' => $parent->id, 'order' => 1]);

        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $parent), [
            'name' => 'Anak',
            'slug' => 'anak',
            'parent_id' => $child->id,
            'order' => 10,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_category_with_children_cannot_be_deleted(): void
    {
        $parent = Category::create(['name' => 'Anak', 'slug' => 'anak', 'order' => 10]);
        Category::create(['name' => 'Bayi', 'slug' => 'anak-bayi', 'parent_id' => $parent->id, 'order' => 1]);

        $response = $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $parent));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_product_filter_includes_descendant_categories(): void
    {
        $parent = Category::create(['name' => 'Anak', 'slug' => 'anak', 'order' => 10]);
        $child = Category::create(['name' => 'Bayi', 'slug' => 'anak-bayi', 'parent_id' => $parent->id, 'order' => 1]);

        Product::create([
            'category_id' => $child->id,
            'name' => 'Kemeja Test',
            'slug' => 'kemeja-test',
            'price' => 100000,
            'image' => 'https://example.com/image.jpg',
        ]);

        $response = $this->get('/products?category=anak');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Products/Index')
            ->has('products.data', 1)
        );
    }

    public function test_expand_ids_includes_descendants(): void
    {
        $parent = Category::create(['name' => 'Anak', 'slug' => 'anak', 'order' => 10]);
        $child = Category::create(['name' => 'Bayi', 'slug' => 'anak-bayi', 'parent_id' => $parent->id, 'order' => 1]);
        $grandchild = Category::create(['name' => 'Newborn', 'slug' => 'anak-bayi-newborn', 'parent_id' => $child->id, 'order' => 1]);

        $expanded = app(CategoryTreeService::class)->expandIds([$parent->id]);

        $this->assertEqualsCanonicalizing([$parent->id, $child->id, $grandchild->id], $expanded);
    }
}
