<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'kind' => 'pj',
            'document' => fake()->numerify('##.###.###/####-##'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('(11) 9####-####'),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn () => [
            'kind' => 'pf',
            'name' => fake()->name(),
            'document' => fake()->numerify('###.###.###-##'),
            'email' => fake()->unique()->safeEmail(),
        ]);
    }
}
