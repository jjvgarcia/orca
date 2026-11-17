<?php

namespace App\Models;

use App\Domain\Money;
use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'customer_id', 'number', 'version', 'status', 'valid_until', 'installments',
    'first_due_days', 'subtotal_cents', 'discount_cents', 'total_cents',
    'decided_at', 'decided_ip', 'decided_user_agent', 'parent_id',
])]
class Quotation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'valid_until' => 'date',
            'decided_at' => 'datetime',
            'version' => 'integer',
            'installments' => 'integer',
            'first_due_days' => 'integer',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<QuotationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /** @return HasMany<QuotationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(QuotationEvent::class)->orderBy('id');
    }

    /** @return HasOne<Order, $this> */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /** @return BelongsTo<Quotation, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * O status persistido nunca é "expired": expiração é derivada da validade.
     */
    public function effectiveStatus(): QuotationStatus
    {
        if ($this->status->isTerminal()) {
            return $this->status;
        }

        if ($this->status === QuotationStatus::Sent && $this->valid_until->isBefore(today())) {
            return QuotationStatus::Expired;
        }

        return $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->effectiveStatus(), [
            QuotationStatus::Draft,
            QuotationStatus::Sent,
            QuotationStatus::Expired,
        ], true);
    }

    public function isAwaitingDecision(): bool
    {
        return $this->effectiveStatus() === QuotationStatus::Sent;
    }

    public function code(): string
    {
        return $this->number.' v'.$this->version;
    }

    public function subtotal(): Money
    {
        return Money::fromCents($this->subtotal_cents);
    }

    public function total(): Money
    {
        return Money::fromCents($this->total_cents);
    }

    public function discount(): Money
    {
        return Money::fromCents($this->discount_cents);
    }
}
