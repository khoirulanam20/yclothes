<?php

namespace Database\Seeders;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeFamily;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $size = Attribute::updateOrCreate(
            ['code' => 'size'],
            [
                'name' => 'Ukuran',
                'type' => AttributeType::Select,
                'is_required' => false,
                'is_filterable' => true,
                'sort_order' => 1,
            ]
        );

        $color = Attribute::updateOrCreate(
            ['code' => 'color'],
            [
                'name' => 'Warna',
                'type' => AttributeType::Select,
                'is_required' => false,
                'is_filterable' => true,
                'sort_order' => 2,
            ]
        );

        $this->seedSizeOptions($size);
        $this->seedColorOptions($color);

        $family = AttributeFamily::updateOrCreate(
            ['name' => 'Fashion Default'],
            ['name' => 'Fashion Default']
        );
        $family->attributes()->sync([$size->id, $color->id]);

        $this->migrateExistingProducts($family, $size, $color);
    }

    private function seedSizeOptions(Attribute $size): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '39', '40', '41', '42', '43'];
        $size->options()->delete();
        foreach ($sizes as $i => $name) {
            AttributeOption::create([
                'attribute_id' => $size->id,
                'name' => $name,
                'sort_order' => $i,
            ]);
        }
    }

    private function seedColorOptions(Attribute $color): void
    {
        $colors = [
            'Hitam', 'Putih', 'Abu-abu', 'Navy', 'Biru', 'Merah', 'Pink',
            'Krem', 'Coklat', 'Hijau', 'Kuning', 'Ungu', 'Orange', 'Beige',
        ];
        $color->options()->delete();
        foreach ($colors as $i => $name) {
            AttributeOption::create([
                'attribute_id' => $color->id,
                'name' => $name,
                'sort_order' => $i,
            ]);
        }
    }

    private function migrateExistingProducts(AttributeFamily $family, Attribute $size, Attribute $color): void
    {
        Product::query()->each(function (Product $product) use ($family, $size, $color) {
            if (! $product->attribute_family_id) {
                $product->attribute_family_id = $family->id;
                $product->save();
            }

            $sizesRaw = $product->getRawOriginal('sizes');
            if ($sizesRaw) {
                $decoded = is_string($sizesRaw) ? json_decode($sizesRaw, true) : $sizesRaw;
                if (is_array($decoded) && $decoded !== []) {
                    ProductAttributeValue::updateOrCreate(
                        ['product_id' => $product->id, 'attribute_id' => $size->id],
                        ['value' => json_encode(array_values($decoded))]
                    );
                }
            }

            $colorsRaw = $product->getRawOriginal('colors');
            if ($colorsRaw) {
                $decoded = is_string($colorsRaw) ? json_decode($colorsRaw, true) : $colorsRaw;
                if (is_array($decoded) && $decoded !== []) {
                    $normalized = array_map(function ($c) {
                        if (is_string($c)) {
                            return ['hex' => $c, 'name' => $c];
                        }

                        return ['hex' => $c['hex'] ?? '', 'name' => $c['name'] ?? $c['hex'] ?? ''];
                    }, $decoded);
                    ProductAttributeValue::updateOrCreate(
                        ['product_id' => $product->id, 'attribute_id' => $color->id],
                        ['value' => json_encode(array_values($normalized))]
                    );
                }
            }
        });
    }
}
