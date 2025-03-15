<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return $user->isAdmin() || $user->id === $order->user_id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order)
    {
        return $user->id === $order->user_id;
    }

    public function updateStatus(User $user, Order $order)
    {
        return $user->isAdmin();
    }
}
