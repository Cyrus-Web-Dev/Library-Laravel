<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_PUSTAKAWAN = 'pustakawan';
    public const ROLE_ANGGOTA = 'anggota';

    public const MEMBER_TYPES = ['Siswa', 'Mahasiswa', 'Guru', 'Umum'];

    public const SECURITY_QUESTIONS = [
        'Siapa nama hewan peliharaan pertama Anda?',
        'Di kota mana Anda dilahirkan?',
        'Apa nama sekolah dasar Anda?',
        'Siapa nama sahabat masa kecil Anda?',
        'Apa makanan favorit Anda?',
    ];

    protected $fillable = [
        'username', 'email', 'password', 'full_name', 'role', 'member_type',
        'phone', 'address', 'avatar', 'bio', 'birth_date', 'gender', 'education',
        'institution', 'member_code', 'social_media', 'sec_question', 'sec_answer',
        'must_change_password', 'status', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token', 'sec_answer'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'last_login' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // ---------------------------------------------------------------- Peran

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isPustakawan(): bool
    {
        return $this->role === self::ROLE_PUSTAKAWAN;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_PUSTAKAWAN], true);
    }

    public function isAnggota(): bool
    {
        return $this->role === self::ROLE_ANGGOTA;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPERADMIN => 'Super Admin (Kepala Pustaka)',
            self::ROLE_PUSTAKAWAN => 'Admin (Pustakawan)',
            default => 'Umum',
        };
    }

    public function roleShort(): string
    {
        return match ($this->role) {
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_PUSTAKAWAN => 'Pustakawan',
            default => 'Umum',
        };
    }

    /** Nomor anggota untuk kartu & QR, mis. AG-00012 */
    public function memberNumber(): string
    {
        return 'AG-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name));
        $letters = array_map(fn ($w) => Str::upper(Str::substr($w, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    }

    // ----------------------------------------------------------- Relasi

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')->withPivot('created_at');
    }

    public function bookRequests(): HasMany
    {
        return $this->hasMany(BookRequest::class);
    }

    public function staffApplications(): HasMany
    {
        return $this->hasMany(StaffApplication::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }
}
