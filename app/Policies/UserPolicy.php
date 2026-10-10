<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manage(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /** Super Admin tidak boleh menurunkan/menonaktifkan/menghapus akun dirinya sendiri. */
    public function modify(User $actor, User $target): bool
    {
        if (! $actor->isSuperadmin()) {
            return false;
        }

        return $actor->id !== $target->id;
    }
}
