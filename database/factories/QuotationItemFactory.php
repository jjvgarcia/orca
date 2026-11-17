<?php

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationItem>
 */
class QuotationItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(10_000, 100_000);

        return [
            'quotation_id' => Quotation::factory(),
            'service_id' => Service::factory(),
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'discount_cents' => 0,
            'total_cents' => $quantity * $unitPrice,
        ];
    }
}
