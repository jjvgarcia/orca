<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'unit' => 'unidade',
            'unit_price_cents' => fake()->numberBetween(10_000, 500_000),
            'active' => true,
        ];
    }
}
