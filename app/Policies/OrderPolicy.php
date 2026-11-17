<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return true;
    }

    public function fulfill(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Open;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Open;
    }
}
