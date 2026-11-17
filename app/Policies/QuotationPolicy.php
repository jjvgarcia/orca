<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function revise(User $user, Quotation $quotation): bool
    {
        return $quotation->isEditable();
    }
}
