<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Akses cepat ke tabel settings, di-cache per request+beberapa menit agar tidak
 * query berulang. Panggil LibrarySettings::forget() setelah menyimpan perubahan.
 */
class LibrarySettings
{
    private const CACHE_KEY = 'library_settings';

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return Setting::query()->pluck('setting_value', 'setting_key')->all();
        });
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        Setting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
