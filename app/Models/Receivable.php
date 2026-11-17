<?php

namespace App\Models;

use App\Domain\Money;
use App\Enums\ReceivableStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'sequence', 'due_date', 'amount_cents'])]
class Receivable extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'due_date' => 'date',
            'amount_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function amount(): Money
    {
        return Money::fromCents($this->amount_cents);
    }

    public function paidCents(): int
    {
        return (int) ($this->paid_cents ?? $this->payments()->sum('amount_cents'));
    }

    public function outstandingCents(): int
    {
        return max(0, $this->amount_cents - $this->paidCents());
    }

    /**
     * Estado derivado na leitura. Vencida tem precedência sobre parcial:
     * dinheiro atrasado é o que importa para quem cobra.
     */
    public function status(): ReceivableStatus
    {
        if ($this->paidCents() >= $this->amount_cents) {
            return ReceivableStatus::Paid;
        }

        if ($this->due_date->isBefore(today())) {
            return ReceivableStatus::Overdue;
        }

        if ($this->paidCents() > 0) {
            return ReceivableStatus::Partial;
        }

        return ReceivableStatus::Open;
    }
}
