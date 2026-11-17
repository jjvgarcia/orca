<?php

namespace App\Models;

use App\Domain\Money;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quotation_id', 'customer_id', 'number', 'status', 'total_cents'])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Quotation, $this> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<Receivable, $this> */
    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class)->orderBy('sequence');
    }

    public function total(): Money
    {
        return Money::fromCents($this->total_cents);
    }

    public function paidCents(): int
    {
        return (int) $this->receivables->sum(fn (Receivable $receivable) => $receivable->paidCents());
    }

    public function outstandingCents(): int
    {
        return (int) $this->receivables->sum(fn (Receivable $receivable) => $receivable->outstandingCents());
    }

    public function hasPayments(): bool
    {
        return $this->receivables()->whereHas('payments')->exists();
    }
}
