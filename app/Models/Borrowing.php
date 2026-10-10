<?php

namespace App\Models;

use App\Services\LibrarySettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    use HasFactory;

    public const REQUESTED = 'requested';
    public const BORROWED = 'borrowed';
    public const RETURNED = 'returned';
    public const LOST = 'lost';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'user_id', 'book_id', 'borrow_date', 'due_date', 'return_date', 'status',
        'extend_count', 'fine_amount', 'fine_paid', 'fine_paid_at', 'request_note',
        'decision_note', 'return_requested_at', 'notes', 'last_reminded', 'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'last_reminded' => 'date',
            'fine_amount' => 'decimal:2',
            'fine_paid' => 'boolean',
            'fine_paid_at' => 'datetime',
            'return_requested_at' => 'datetime',
        ];
    }

    // ----------------------------------------------------------- Relasi

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // ----------------------------------------------------------- Denda

    /** Tanggal acuan pengembalian: saat anggota mengajukan (jika ada), bukan saat staf mengonfirmasi */
    public function effectiveReturnDate(): Carbon
    {
        if ($this->return_requested_at) {
            return $this->return_requested_at->copy()->startOfDay()->min(now()->startOfDay());
        }

        return now()->startOfDay();
    }

    public function lateDays(?Carbon $reference = null): int
    {
        if (! $this->due_date) {
            return 0;
        }
        $reference ??= $this->return_date ?? $this->effectiveReturnDate();
        $due = $this->due_date->copy()->startOfDay();
        $ref = $reference->copy()->startOfDay();

        return $ref->greaterThan($due) ? $due->diffInDays($ref) : 0;
    }

    public function calculateFine(?Carbon $reference = null): float
    {
        $days = $this->lateDays($reference);
        if ($days <= 0) {
            return 0.0;
        }

        $perDay = (float) LibrarySettings::get('fine_per_day', '1000');
        $max = (float) LibrarySettings::get('fine_max', '20000');
        $fine = $days * $perDay;

        return $max > 0 ? min($fine, $max) : $fine;
    }

    // ----------------------------------------------------- Status tampilan

    /**
     * @return array{label: string, tone: string, lateDays: int, fine: float}
     */
    public function state(): array
    {
        return match ($this->status) {
            self::REQUESTED => ['label' => 'Menunggu persetujuan', 'tone' => 'warn', 'lateDays' => 0, 'fine' => 0.0],
            self::REJECTED => ['label' => 'Permintaan ditolak', 'tone' => 'danger', 'lateDays' => 0, 'fine' => 0.0],
            self::CANCELLED => ['label' => 'Dibatalkan', 'tone' => 'muted', 'lateDays' => 0, 'fine' => 0.0],
            self::RETURNED => $this->fine_amount > 0 && ! $this->fine_paid
                ? ['label' => 'Dikembalikan · denda belum lunas', 'tone' => 'warn', 'lateDays' => 0, 'fine' => (float) $this->fine_amount]
                : ['label' => 'Dikembalikan', 'tone' => 'ok', 'lateDays' => 0, 'fine' => (float) $this->fine_amount],
            self::LOST => $this->fine_paid
                ? ['label' => 'Hilang · lunas', 'tone' => 'muted', 'lateDays' => 0, 'fine' => (float) $this->fine_amount]
                : ['label' => 'Hilang · denda belum lunas', 'tone' => 'danger', 'lateDays' => 0, 'fine' => (float) $this->fine_amount],
            default => $this->borrowedState(),
        };
    }

    private function borrowedState(): array
    {
        if ($this->return_requested_at) {
            $ref = $this->effectiveReturnDate();

            return [
                'label' => 'Menunggu konfirmasi pengembalian',
                'tone' => 'info',
                'lateDays' => $this->lateDays($ref),
                'fine' => $this->calculateFine($ref),
            ];
        }

        $late = $this->lateDays(now());
        if ($late > 0) {
            return ['label' => "Terlambat {$late} hari", 'tone' => 'danger', 'lateDays' => $late, 'fine' => $this->calculateFine(now())];
        }

        if (! $this->due_date) {
            return ['label' => 'Dipinjam', 'tone' => 'info', 'lateDays' => 0, 'fine' => 0.0];
        }
        $daysLeft = now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay());
        if ($daysLeft === 0) {
            return ['label' => 'Jatuh tempo hari ini', 'tone' => 'warn', 'lateDays' => 0, 'fine' => 0.0];
        }

        return [
            'label' => $daysLeft <= 2 ? "Sisa {$daysLeft} hari" : 'Dipinjam',
            'tone' => $daysLeft <= 2 ? 'warn' : 'info',
            'lateDays' => 0,
            'fine' => 0.0,
        ];
    }

    public static function generateCode(): string
    {
        return 'PIN' . now()->format('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}
