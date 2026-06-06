<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'type' => ProductType::Simple,
            'price' => fake()->numberBetween(50_000, 500_000),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'is_active' => true,
            'track_stock' => false,
            'allow_backorder' => false,
            'weight' => 500,
        ];
    }

    public function tracked(int $stock = 10): static
    {
        return $this->state(fn () => [
            'track_stock' => true,
            'allow_backorder' => false,
        ]);
    }
}
