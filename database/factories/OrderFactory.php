<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(100_000, 500_000);

        return [
            'order_number' => 'INV-'.fake()->unique()->numerify('######'),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('08##########'),
            'customer_email' => fake()->safeEmail(),
            'shipping_address' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_cost' => 15_000,
            'total_price' => $total,
            'grand_total' => $total + 15_000,
            'payment_method' => 'bank_transfer',
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone ?? fake()->numerify('08##########'),
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Order $order) {
            if (empty($order->access_token)) {
                $order->access_token = fake()->sha256();
            }
        })->afterCreating(function (Order $order) {
            if (! $order->payment_status) {
                $order->updateTrusted([
                    'payment_status' => 'pending',
                    'payment_confirmation_status' => 'none',
                    'order_status' => 'pending',
                ]);
            }
        });
    }

    public function paid(): static
    {
        return $this->afterCreating(function (Order $order) {
            $order->updateTrusted([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'order_status' => 'confirmed',
            ]);
        });
    }
}
