<?php

namespace App\Models;

use App\Domain\Money;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'receivable_id', 'amount_cents', 'method', 'paid_at',
    'idempotency_key', 'fingerprint',
])]
class Payment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'method' => PaymentMethod::class,
            'paid_at' => 'date',
        ];
    }

    /** @return BelongsTo<Receivable, $this> */
    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function amount(): Money
    {
        return Money::fromCents($this->amount_cents);
    }
}
