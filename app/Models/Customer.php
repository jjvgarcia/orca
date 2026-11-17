<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'kind', 'document', 'email', 'phone'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /** @return HasMany<Quotation, $this> */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isCompany(): bool
    {
        return $this->kind === 'pj';
    }

    public function kindLabel(): string
    {
        return $this->isCompany() ? 'PJ' : 'PF';
    }
}
