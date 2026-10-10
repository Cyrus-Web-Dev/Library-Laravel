<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /** Siapa pun (termasuk tamu) boleh melihat buku yang belum diarsipkan; staf boleh melihat semua. */
    public function view(?User $user, Book $book): bool
    {
        return $book->status !== Book::STATUS_ARCHIVED || ($user && $user->isStaff());
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Book $book): bool
    {
        return $user->isStaff();
    }

    /** Hanya buku tanpa riwayat peminjaman yang boleh dihapus permanen (ditegakkan ulang di controller). */
    public function delete(User $user, Book $book): bool
    {
        return $user->isStaff();
    }

    public function manageCatalog(User $user): bool
    {
        return $user->isStaff();
    }
}
