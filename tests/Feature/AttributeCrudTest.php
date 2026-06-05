<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeFamily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_admin_attribute_families_index(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/attribute-families')
            ->assertStatus(200)
            ->assertSee('Fashion Default');
    }

    public function test_admin_attribute_family_store(): void
    {
        $attr = Attribute::where('code', 'size')->first();

        $this->actingAs($this->admin)
            ->post('/admin/attribute-families', [
                'name' => 'Test Family',
                'attribute_ids' => [$attr->id],
            ])
            ->assertRedirect('/admin/attribute-families');

        $this->assertDatabaseHas('attribute_families', ['name' => 'Test Family']);
    }

    public function test_admin_attributes_index(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/attributes')
            ->assertStatus(200)
            ->assertSee('Ukuran')
            ->assertSee('Warna');
    }

    public function test_admin_attribute_store_with_options(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/attributes', [
                'name' => 'Bahan',
                'code' => 'material',
                'type' => AttributeType::Select->value,
                'is_required' => '1',
                'is_filterable' => '1',
                'sort_order' => 5,
                'options' => [
                    ['name' => 'Katun', 'sort_order' => 0],
                    ['name' => 'Polyester', 'sort_order' => 1],
                ],
            ])
            ->assertRedirect('/admin/attributes');

        $attribute = Attribute::where('code', 'material')->first();
        $this->assertNotNull($attribute);
        $this->assertCount(2, $attribute->options);
    }

    public function test_admin_attribute_update(): void
    {
        $attribute = Attribute::where('code', 'size')->first();

        $this->actingAs($this->admin)
            ->put("/admin/attributes/{$attribute->id}", [
                'name' => 'Ukuran Produk',
                'code' => 'size',
                'type' => AttributeType::Multiselect->value,
                'sort_order' => 1,
                'options' => [
                    ['name' => 'S', 'sort_order' => 0],
                    ['name' => 'M', 'sort_order' => 1],
                ],
            ])
            ->assertRedirect('/admin/attributes');

        $this->assertEquals('Ukuran Produk', $attribute->fresh()->name);
    }

    public function test_admin_attribute_delete_blocked_when_used(): void
    {
        $attribute = Attribute::where('code', 'size')->first();

        $this->actingAs($this->admin)
            ->delete("/admin/attributes/{$attribute->id}")
            ->assertRedirect('/admin/attributes');

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
    }

    public function test_admin_attribute_family_delete(): void
    {
        $family = AttributeFamily::create(['name' => 'Hapus Test']);

        $this->actingAs($this->admin)
            ->delete("/admin/attribute-families/{$family->id}")
            ->assertRedirect('/admin/attribute-families');

        $this->assertDatabaseMissing('attribute_families', ['id' => $family->id]);
    }
}
