<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * OTP generik berbasis cache, dipakai untuk verifikasi email registrasi
 * dan reset password. Key disarankan berformat "register:email" atau "reset:email".
 */
class OtpService
{
    public function generate(string $key): string
    {
        $length = (int) config('gmail.otp_length', 6);
        $code = (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);

        $ttl = now()->addMinutes((int) config('gmail.otp_ttl_minutes', 10));
        Cache::put("otp:{$key}:code", $code, $ttl);
        Cache::put("otp:{$key}:attempts", 0, $ttl);

        return $code;
    }

    /** @return array{0: bool, 1: ?string} [valid, pesan_error] */
    public function verify(string $key, string $inputCode): array
    {
        $maxAttempts = (int) config('gmail.otp_max_attempts', 5);
        $attempts = (int) Cache::get("otp:{$key}:attempts", 0);

        if ($attempts >= $maxAttempts) {
            return [false, 'Terlalu banyak percobaan salah. Silakan minta kode baru.'];
        }

        $storedCode = Cache::get("otp:{$key}:code");
        if (! $storedCode) {
            return [false, 'Kode sudah kedaluwarsa. Silakan minta kode baru.'];
        }

        if (! hash_equals((string) $storedCode, trim($inputCode))) {
            Cache::increment("otp:{$key}:attempts");
            $remaining = $maxAttempts - ($attempts + 1);

            return [false, $remaining > 0 ? "Kode salah. Sisa percobaan: {$remaining}." : 'Kode salah. Silakan minta kode baru.'];
        }

        $this->clear($key);

        return [true, null];
    }

    public function clear(string $key): void
    {
        Cache::forget("otp:{$key}:code");
        Cache::forget("otp:{$key}:attempts");
    }

    public function canResend(string $key): bool
    {
        $availableAt = Cache::get("otp:{$key}:resend_available_at");

        return ! $availableAt || now()->timestamp >= $availableAt;
    }

    public function markResent(string $key): void
    {
        $cooldown = (int) config('gmail.otp_resend_cooldown_seconds', 60);
        Cache::put("otp:{$key}:resend_available_at", now()->addSeconds($cooldown)->timestamp, now()->addSeconds($cooldown + 5));
    }

    public function secondsUntilResendAllowed(string $key): int
    {
        $availableAt = Cache::get("otp:{$key}:resend_available_at");

        return $availableAt ? max(0, $availableAt - now()->timestamp) : 0;
    }
}
