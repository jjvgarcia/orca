<?php

namespace App\Models;

use App\Domain\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'unit', 'unit_price_cents', 'active'])]
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    /** @param Builder<Service> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /** @return HasMany<QuotationItem, $this> */
    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function price(): Money
    {
        return Money::fromCents($this->unit_price_cents);
    }
}
