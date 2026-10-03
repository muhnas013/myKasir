<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Owner/admin melihat semua; kasir hanya pesanannya sendiri. */
    public function view(User $user, Order $order): bool
    {
        return $user->is_active && ($user->isOwnerOrAdmin() || $order->user_id === $user->id);
    }

    /** Semua peran boleh memicu void; kasir wajib persetujuan PIN (ditegakkan di VoidOrder). */
    public function void(User $user): bool
    {
        return $user->is_active;
    }
}
