<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookRequest extends Model
{
    use HasFactory;

    protected $table = 'book_requests';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const FULFILLED = 'fulfilled';

    protected $fillable = [
        'user_id', 'title', 'author', 'genre', 'type', 'publisher', 'notes',
        'status', 'admin_notes', 'handled_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function stateLabel(): array
    {
        return match ($this->status) {
            self::APPROVED => ['label' => 'Disetujui', 'tone' => 'info'],
            self::REJECTED => ['label' => 'Ditolak', 'tone' => 'danger'],
            self::FULFILLED => ['label' => 'Sudah tersedia', 'tone' => 'ok'],
            default => ['label' => 'Menunggu', 'tone' => 'warn'],
        };
    }
}
