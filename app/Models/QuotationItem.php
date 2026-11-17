<?php

namespace App\Models;

use App\Domain\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotation_id', 'service_id', 'description', 'quantity',
    'unit_price_cents', 'discount_cents', 'total_cents',
])]
class QuotationItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Quotation, $this> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function unitPrice(): Money
    {
        return Money::fromCents($this->unit_price_cents);
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
