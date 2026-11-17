<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'number' => 'Q-'.now()->year.'-'.fake()->unique()->numberBetween(1000, 9999),
            'version' => 1,
            'status' => QuotationStatus::Draft,
            'valid_until' => today()->addDays(15),
            'installments' => 1,
            'first_due_days' => 30,
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 0,
        ];
    }
}
