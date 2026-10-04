<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAll(User $user): bool
    {
        return $user->isOwnerOrAdmin();
    }

    public function viewOwn(User $user): bool
    {
        return true;
    }
}
