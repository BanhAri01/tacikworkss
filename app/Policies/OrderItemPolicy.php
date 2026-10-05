<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\User;

class OrderItemPolicy
{
    public function download(User $user, OrderItem $item): bool
    {
        return $item->order->buyer_id === $user->id
            && $item->order->status === OrderStatus::Paid
            && $item->refunded_at === null;
    }
}
