<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_id' => Quotation::factory(),
            'customer_id' => Customer::factory(),
            'number' => now()->year.'-'.fake()->unique()->numerify('####'),
            'status' => OrderStatus::Open,
            'total_cents' => fake()->numberBetween(10_000, 500_000),
        ];
    }
}
