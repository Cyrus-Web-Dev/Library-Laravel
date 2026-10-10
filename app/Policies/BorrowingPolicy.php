<?php

namespace App\Policies;

use App\Models\Borrowing;
use App\Models\User;

class BorrowingPolicy
{
    public function view(User $user, Borrowing $borrowing): bool
    {
        return $user->isStaff() || $borrowing->user_id === $user->id;
    }

    /** Hanya anggota (bukan staf) yang mengajukan pinjam/kembali untuk dirinya sendiri. */
    public function requestAsMember(User $user): bool
    {
        return $user->isAnggota();
    }

    public function manage(User $user): bool
    {
        return $user->isStaff();
    }

    /** Anggota hanya boleh membatalkan/mengajukan kembali miliknya sendiri. */
    public function actAsOwner(User $user, Borrowing $borrowing): bool
    {
        return $user->isAnggota() && $borrowing->user_id === $user->id;
    }
}
