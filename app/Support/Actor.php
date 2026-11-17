<?php

namespace App\Support;

use App\Models\User;

final class Actor
{
    public static function from(?User $user): string
    {
        return $user === null ? 'system' : 'user:'.$user->email;
    }
}
