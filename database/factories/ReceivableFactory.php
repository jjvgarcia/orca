<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receivable>
 */
class ReceivableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'sequence' => 1,
            'due_date' => today()->addDays(30),
            'amount_cents' => fake()->numberBetween(10_000, 200_000),
        ];
    }
}
