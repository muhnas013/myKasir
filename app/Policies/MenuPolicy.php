<?php

namespace App\Policies;

use App\Models\User;

class MenuPolicy
{
    public function manage(User $user): bool
    {
        return $user->isOwnerOrAdmin();
    }
}
