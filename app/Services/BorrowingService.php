<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seluruh alur peminjaman: anggota mengajukan -> staf menyetujui/menolak ->
 * anggota mengajukan kembali -> staf mengonfirmasi/menolak -> (opsional) hilang/denda.
 * Dipakai oleh BorrowingController (web/Inertia) dan Api\BorrowingController (REST) agar
 * aturannya konsisten di kedua jalur. Setiap metode melempar RuntimeException dengan
 * pesan yang aman ditampilkan ke pengguna bila aksi ditolak.
 */
class BorrowingService
{
    /** Alasan anggota tidak boleh mengajukan pinjam (null = boleh). */
    public function blockReason(User $member, bool $forApproval = false): ?string
    {
        $statuses = $forApproval ? ['borrowed'] : ['borrowed', 'requested'];
        $active = Borrowing::where('user_id', $member->id)->whereIn('status', $statuses)->count();
        $max = (int) LibrarySettings::get('max_books_per_user', '3');

        if ($active >= $max) {
            return $forApproval
                ? "Sudah mencapai batas {$max} buku yang sedang dipinjam."
                : "Batas {$max} buku tercapai (termasuk permintaan yang masih menunggu).";
        }

        if (LibrarySettings::get('block_if_fine', '1') === '1') {
            $overdue = Borrowing::where('user_id', $member->id)->where('status', 'borrowed')->where('due_date', '<', now()->toDateString())->exists();
            if ($overdue) {
                return 'Masih ada buku yang terlambat dikembalikan.';
            }
            $unpaid = Borrowing::where('user_id', $member->id)->where('fine_amount', '>', 0)->where('fine_paid', false)->exists();
            if ($unpaid) {
                return 'Masih ada denda yang belum lunas.';
            }
        }

        return null;
    }

    public function requestBorrow(User $member, Book $book, ?string $note): Borrowing
    {
        if ($book->status !== Book::STATUS_AVAILABLE) {
            throw new RuntimeException('Buku ini tidak bisa dipinjam.');
        }

        $existing = Borrowing::where('user_id', $member->id)->where('book_id', $book->id)
            ->whereIn('status', ['requested', 'borrowed'])->first();
        if ($existing) {
            throw new RuntimeException($existing->status === 'requested'
                ? 'Permintaan untuk buku ini sudah diajukan dan menunggu persetujuan.'
                : 'Anda sedang meminjam buku ini.');
        }

        if ($book->available_stock < 1) {
            throw new RuntimeException('Stok buku sedang habis.');
        }

        if ($reason = $this->blockReason($member)) {
            throw new RuntimeException($reason);
        }

        $borrowing = Borrowing::create([
            'code' => Borrowing::generateCode(),
            'user_id' => $member->id,
            'book_id' => $book->id,
            'borrow_date' => now()->toDateString(),
            'due_date' => now()->toDateString(), // diisi ulang saat disetujui
            'status' => Borrowing::REQUESTED,
            'request_note' => $note,
        ]);

        Notification::notifyStaff('Permintaan peminjaman', "{$member->full_name} ingin meminjam \"{$book->title}\".");

        return $borrowing;
    }

    public function cancelRequest(Borrowing $borrowing): void
    {
        if ($borrowing->status !== Borrowing::REQUESTED) {
            throw new RuntimeException('Permintaan ini sudah ditanggapi, tidak bisa dibatalkan.');
        }
        $borrowing->update(['status' => Borrowing::CANCELLED]);
    }

    public function approve(Borrowing $borrowing, User $staff, int $days, ?string $note): void
    {
        DB::transaction(function () use ($borrowing, $staff, $days, $note) {
            $borrowing = Borrowing::whereKey($borrowing->id)->lockForUpdate()->firstOrFail();
            if ($borrowing->status !== Borrowing::REQUESTED) {
                throw new RuntimeException('Permintaan ini sudah ditanggapi atau dibatalkan anggota.');
            }

            $book = Book::whereKey($borrowing->book_id)->lockForUpdate()->firstOrFail();
            if ($book->status !== Book::STATUS_AVAILABLE) {
                throw new RuntimeException('Buku ini sedang tidak dipinjamkan.');
            }
            if ($book->available_stock < 1) {
                throw new RuntimeException('Stok buku habis, belum bisa disetujui.');
            }

            $member = User::findOrFail($borrowing->user_id);
            if ($member->status !== 'active') {
                throw new RuntimeException('Akun anggota tidak aktif.');
            }
            if ($reason = $this->blockReason($member, forApproval: true)) {
                throw new RuntimeException($reason);
            }

            $days = max(1, min(60, $days));
            $borrowing->update([
                'status' => Borrowing::BORROWED,
                'borrow_date' => now()->toDateString(),
                'due_date' => now()->addDays($days)->toDateString(),
                'decision_note' => $note,
                'handled_by' => $staff->id,
                'last_reminded' => null,
            ]);
            $book->decrement('available_stock');

            Notification::send($member->id, 'Permintaan pinjam disetujui',
                "\"{$book->title}\" siap diambil. Kembalikan paling lambat ".$borrowing->due_date->translatedFormat('d M Y').'.'.($note ? " Catatan: {$note}" : ''),
                'success');
        });
    }

    public function reject(Borrowing $borrowing, User $staff, ?string $note): void
    {
        if ($borrowing->status !== Borrowing::REQUESTED) {
            throw new RuntimeException('Permintaan ini sudah ditanggapi atau dibatalkan anggota.');
        }

        $borrowing->update(['status' => Borrowing::REJECTED, 'decision_note' => $note, 'handled_by' => $staff->id]);

        Notification::send($borrowing->user_id, 'Permintaan pinjam ditolak',
            "Permintaan \"{$borrowing->book->title}\" belum bisa disetujui.".($note ? " Alasan: {$note}" : ''), 'warning');
    }

    public function requestReturn(Borrowing $borrowing): void
    {
        if ($borrowing->status !== Borrowing::BORROWED) {
            throw new RuntimeException('Buku ini tidak sedang Anda pinjam.');
        }
        if ($borrowing->return_requested_at) {
            throw new RuntimeException('Pengajuan pengembalian sudah dikirim, tinggal menunggu konfirmasi.');
        }

        $borrowing->update(['return_requested_at' => now()]);

        Notification::notifyStaff('Pengajuan pengembalian', "{$borrowing->user->full_name} mengajukan pengembalian \"{$borrowing->book->title}\".");
    }

    public function cancelReturnRequest(Borrowing $borrowing): void
    {
        if (! $borrowing->return_requested_at) {
            throw new RuntimeException('Tidak ada pengajuan pengembalian untuk dibatalkan.');
        }
        $borrowing->update(['return_requested_at' => null]);
    }

    public function confirmReturn(Borrowing $borrowing, User $staff): void
    {
        if ($borrowing->status !== Borrowing::BORROWED) {
            throw new RuntimeException('Buku ini sudah tidak berstatus dipinjam.');
        }

        DB::transaction(function () use ($borrowing, $staff) {
            $reference = $borrowing->effectiveReturnDate();
            $fine = $borrowing->calculateFine($reference);

            $borrowing->update([
                'status' => Borrowing::RETURNED,
                'return_requested_at' => null,
                'return_date' => $reference->toDateString(),
                'fine_amount' => $fine,
                'fine_paid' => $fine <= 0,
                'handled_by' => $staff->id,
            ]);

            $borrowing->book->syncAvailableStock();

            $title = $borrowing->book->title;
            if ($fine > 0) {
                Notification::send($borrowing->user_id, 'Buku dikembalikan — ada denda',
                    "\"{$title}\" terlambat {$borrowing->lateDays($reference)} hari. Denda Rp ".number_format($fine, 0, ',', '.').'.', 'warning');
            } else {
                Notification::send($borrowing->user_id, 'Pengembalian berhasil', "\"{$title}\" sudah dikembalikan. Terima kasih!", 'success');
            }
        });
    }

    public function rejectReturn(Borrowing $borrowing, User $staff, ?string $note): void
    {
        if (! $borrowing->return_requested_at) {
            throw new RuntimeException('Tidak ada pengajuan pengembalian untuk buku ini.');
        }

        $borrowing->update(['return_requested_at' => null]);

        Notification::send($borrowing->user_id, 'Pengembalian belum dikonfirmasi',
            "Pengajuan pengembalian \"{$borrowing->book->title}\" belum bisa dikonfirmasi.".($note ? " Catatan: {$note}" : ' Buku belum diterima pustakawan.'), 'warning');
    }

    public function extend(Borrowing $borrowing): void
    {
        if ($borrowing->status !== Borrowing::BORROWED) {
            throw new RuntimeException('Hanya peminjaman aktif yang bisa diperpanjang.');
        }
        if ($borrowing->return_requested_at) {
            throw new RuntimeException('Anggota sudah mengajukan pengembalian. Konfirmasi atau tolak dulu.');
        }
        if ($borrowing->lateDays(now()) > 0) {
            throw new RuntimeException('Sudah terlambat, tidak bisa diperpanjang. Proses pengembalian dulu.');
        }

        $max = (int) LibrarySettings::get('max_extend', '1');
        if ($borrowing->extend_count >= $max) {
            throw new RuntimeException("Batas perpanjangan ({$max} kali) sudah tercapai.");
        }

        $addDays = (int) LibrarySettings::get('extend_days', '7');
        $newDue = $borrowing->due_date->copy()->addDays($addDays);
        $borrowing->update(['due_date' => $newDue, 'extend_count' => $borrowing->extend_count + 1, 'last_reminded' => null]);

        Notification::send($borrowing->user_id, 'Peminjaman diperpanjang', "\"{$borrowing->book->title}\" kini jatuh tempo ".$newDue->translatedFormat('d M Y').'.', 'info');
    }

    public function markLost(Borrowing $borrowing, User $staff): void
    {
        if ($borrowing->status !== Borrowing::BORROWED) {
            throw new RuntimeException('Hanya peminjaman aktif yang bisa dinyatakan hilang.');
        }

        DB::transaction(function () use ($borrowing, $staff) {
            $book = $borrowing->book;
            $fine = $book->price > 0 ? (float) $book->price : (float) LibrarySettings::get('fine_lost', '50000');
            $fine += $borrowing->calculateFine(now());

            $borrowing->update([
                'status' => Borrowing::LOST,
                'return_date' => now()->toDateString(),
                'fine_amount' => $fine,
                'fine_paid' => false,
                'handled_by' => $staff->id,
            ]);

            $book->decrement('total_stock');
            $book->syncAvailableStock();

            Notification::send($borrowing->user_id, 'Buku dinyatakan hilang',
                "\"{$book->title}\" dinyatakan hilang. Denda pengganti Rp ".number_format($fine, 0, ',', '.').'.', 'danger');
        });
    }

    public function payFine(Borrowing $borrowing, User $staff): void
    {
        if ($borrowing->fine_amount <= 0 || $borrowing->fine_paid || $borrowing->status === Borrowing::BORROWED) {
            throw new RuntimeException('Tidak ada denda yang perlu dibayar untuk transaksi ini.');
        }

        $borrowing->update(['fine_paid' => true, 'fine_paid_at' => now(), 'handled_by' => $staff->id]);

        Notification::send($borrowing->user_id, 'Denda lunas',
            'Pembayaran denda Rp '.number_format($borrowing->fine_amount, 0, ',', '.')." untuk \"{$borrowing->book->title}\" telah diterima.", 'success');
    }
}
