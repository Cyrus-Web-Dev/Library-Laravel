<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    /** Pastikan cover_url ikut ter-serialize ke Inertia/JSON */
    protected $appends = ['cover_url'];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'isbn', 'title', 'author', 'publisher', 'publication_year', 'genre_id', 'type_id',
        'description', 'cover_image', 'ebook_file', 'location', 'total_stock', 'available_stock',
        'pages', 'language', 'price', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_stock' => 'integer',
            'available_stock' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Kode buku (BK-0001) diisi otomatis sesudah baris tersimpan dan id tersedia.
        static::created(function (Book $book) {
            if (! $book->book_code) {
                $book->forceFill(['book_code' => $book->generateCode()])->saveQuietly();
            }
        });
    }

    public function generateCode(): string
    {
        return 'BK-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    // ----------------------------------------------------------- Relasi

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BookType::class, 'type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function approvedRatings(): HasMany
    {
        return $this->ratings()->where('is_approved', true);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    // ----------------------------------------------------------- Scope

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', '!=', self::STATUS_ARCHIVED);
    }

    public function scopeAvailableToBorrow(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_AVAILABLE)->where('available_stock', '>', 0);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            $like = "%{$term}%";
            $w->where('title', 'ilike', $like)
                ->orWhere('author', 'ilike', $like)
                ->orWhere('publisher', 'ilike', $like)
                ->orWhere('isbn', 'ilike', $like)
                ->orWhere('book_code', 'ilike', $like)
                ->orWhere('description', 'ilike', $like);
        });
    }

    // ----------------------------------------------------------- Helper

    public function averageRating(): float
    {
        return round((float) $this->approvedRatings()->avg('rating'), 1);
    }

    public function ratingsCount(): int
    {
        return $this->approvedRatings()->count();
    }

    public function isBorrowable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->available_stock > 0;
    }

    /** Hitung ulang stok tersedia dari data peminjaman aktif (sinkronisasi, bukan sumber kebenaran) */
    public function syncAvailableStock(): void
    {
        $borrowed = $this->borrowings()->where('status', 'borrowed')->count();
        $this->forceFill(['available_stock' => max(0, $this->total_stock - $borrowed)])->saveQuietly();
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/'.$this->cover_image) : null;
    }

}
