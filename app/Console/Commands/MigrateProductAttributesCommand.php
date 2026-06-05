<?php

namespace App\Console\Commands;

use App\Models\Attribute;
use App\Models\AttributeFamily;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Console\Command;

class MigrateProductAttributesCommand extends Command
{
    protected $signature = 'attributes:migrate-from-json';

    protected $description = 'Migrasi products.sizes dan products.colors JSON ke product_attribute_values';

    public function handle(): int
    {
        $family = AttributeFamily::where('name', 'Fashion Default')->first();
        $sizeAttr = Attribute::where('code', 'size')->first();
        $colorAttr = Attribute::where('code', 'color')->first();

        if (! $family || ! $sizeAttr || ! $colorAttr) {
            $this->error('Jalankan AttributeSeeder terlebih dahulu.');

            return self::FAILURE;
        }

        $migrated = 0;

        Product::query()->chunkById(50, function ($products) use ($family, $sizeAttr, $colorAttr, &$migrated) {
            foreach ($products as $product) {
                $updated = false;

                if (! $product->attribute_family_id) {
                    $product->attribute_family_id = $family->id;
                    $updated = true;
                }

                $sizes = $product->getRawOriginal('sizes');
                if ($sizes) {
                    $decoded = is_string($sizes) ? json_decode($sizes, true) : $sizes;
                    if (is_array($decoded) && $decoded !== []) {
                        ProductAttributeValue::updateOrCreate(
                            ['product_id' => $product->id, 'attribute_id' => $sizeAttr->id],
                            ['value' => json_encode(array_values($decoded))]
                        );
                        $updated = true;
                    }
                }

                $colors = $product->getRawOriginal('colors');
                if ($colors) {
                    $decoded = is_string($colors) ? json_decode($colors, true) : $colors;
                    if (is_array($decoded) && $decoded !== []) {
                        $normalized = array_map(function ($c) {
                            if (is_string($c)) {
                                return ['hex' => $c, 'name' => $c];
                            }

                            return ['hex' => $c['hex'] ?? '', 'name' => $c['name'] ?? $c['hex'] ?? ''];
                        }, $decoded);
                        ProductAttributeValue::updateOrCreate(
                            ['product_id' => $product->id, 'attribute_id' => $colorAttr->id],
                            ['value' => json_encode(array_values($normalized))]
                        );
                        $updated = true;
                    }
                }

                if ($updated) {
                    $product->save();
                    $migrated++;
                }
            }
        });

        $this->info("Migrasi selesai untuk {$migrated} produk.");

        return self::SUCCESS;
    }
}
