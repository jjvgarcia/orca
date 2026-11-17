<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quotation_id', 'from_status', 'to_status', 'actor', 'meta', 'created_at'])]
class QuotationEvent extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'from_status' => QuotationStatus::class,
            'to_status' => QuotationStatus::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Quotation, $this> */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
