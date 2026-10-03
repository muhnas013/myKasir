<?php

namespace App\Policies;

use App\Models\User;

class PosPolicy
{
    /** Semua peran aktif boleh bertransaksi (matriks 05). */
    public function transact(User $user): bool
    {
        return $user->is_active;
    }
}
