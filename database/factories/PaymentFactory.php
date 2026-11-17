<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receivable_id' => Receivable::factory(),
            'amount_cents' => fake()->numberBetween(1_000, 100_000),
            'method' => PaymentMethod::Pix,
            'paid_at' => today(),
            'idempotency_key' => (string) Str::uuid(),
            'fingerprint' => hash('sha256', 'factory'),
        ];
    }
}
