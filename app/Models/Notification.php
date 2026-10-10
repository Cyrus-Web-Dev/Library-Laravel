<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $table = 'notifications';

    protected $fillable = ['user_id', 'title', 'message', 'type', 'is_read', 'link', 'created_at'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function send(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        static::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'created_at' => now(),
        ]);
    }

    /** Kirim ke semua staf (atau hanya superadmin) */
    public static function notifyStaff(string $title, string $message, ?string $link = null, bool $superadminOnly = false): void
    {
        $roles = $superadminOnly ? [User::ROLE_SUPERADMIN] : [User::ROLE_SUPERADMIN, User::ROLE_PUSTAKAWAN];
        User::whereIn('role', $roles)->where('status', 'active')->pluck('id')
            ->each(fn ($id) => static::send($id, $title, $message, 'info', $link));
    }
}
