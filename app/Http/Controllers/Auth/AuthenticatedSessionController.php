<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\LibrarySettings;
use App\Services\OtpMailer;
use App\Services\OtpService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Langkah 1 login: cek username/password.
     * Jika login_otp aktif (default ya) ATAU email belum terverifikasi → kirim OTP & minta kode.
     * Jika tidak → langsung masuk.
     */
    public function store(Request $request, OtpService $otpService, OtpMailer $otpMailer)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $throttleKey = Str::transliterate(mb_strtolower($login).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages(['login' => "Terlalu banyak percobaan gagal. Coba lagi dalam {$seconds} detik."]);
        }

        $user = User::where('username', $login)->orWhere('email', mb_strtolower($login))->first();

        $passwordOk = Hash::check($request->input('password'), $user?->password ?? Hash::make('dummy-pemerataan-waktu'));

        if (! $user || ! $passwordOk) {
            RateLimiter::hit($throttleKey, 600);
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }

        if ($user->status !== 'active') {
            RateLimiter::clear($throttleKey);
            throw ValidationException::withMessages(['login' => match ($user->status) {
                'banned' => 'Akun ini diblokir. Hubungi pustakawan.',
                default => 'Akun ini dinonaktifkan. Hubungi pustakawan.',
            }]);
        }

        if (Hash::needsRehash($user->password)) {
            $user->password = $request->input('password');
            $user->save();
        }

        RateLimiter::clear($throttleKey);

        // OTP wajib jika email belum terverifikasi, atau pengaturan login_otp aktif (default: aktif)
        $requireOtp = empty($user->email_verified_at)
            || LibrarySettings::get('login_otp', '1') === '1';

        if ($requireOtp) {
            $key = 'login:'.$user->id;

            if (! $otpService->canResend($key)) {
                // Masih dalam cooldown — arahkan ke halaman OTP dengan sisa waktu
                $request->session()->put('login.pending_user_id', $user->id);
                $request->session()->put('login.remember', $request->boolean('remember'));

                return redirect()->route('login.verify')->with(
                    'flash.info',
                    'Kode verifikasi masih aktif. Masukkan kode dari email Anda.'
                );
            }

            $code = $otpService->generate($key);
            $otpService->markResent($key);

            [$sent, $error, $note] = $otpMailer->send(
                $user->email,
                $user->full_name,
                'Kode Login — '.LibrarySettings::get('library_name', 'Perpustakaan Nusantara'),
                'Gunakan kode berikut untuk menyelesaikan login Anda:',
                $code
            );

            if (! $sent) {
                throw ValidationException::withMessages([
                    'login' => 'Password benar, tetapi gagal mengirim kode verifikasi ke email. '.$error,
                ]);
            }

            $request->session()->put('login.pending_user_id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            return redirect()->route('login.verify')->with(
                'flash.success',
                $note ?: 'Kode verifikasi sudah dikirim ke email Anda. Masukkan kode untuk masuk.'
            );
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    public function showVerify(Request $request, OtpService $otpService): Response|\Illuminate\Http\RedirectResponse
    {
        $userId = $request->session()->get('login.pending_user_id');
        if (! $userId) {
            return redirect()->route('login')->with('flash.warning', 'Sesi login berakhir. Silakan masuk ulang.');
        }

        $user = User::find($userId);
        if (! $user) {
            $request->session()->forget(['login.pending_user_id', 'login.remember']);

            return redirect()->route('login');
        }

        $key = 'login:'.$user->id;

        return Inertia::render('Auth/LoginVerify', [
            'maskedEmail' => $this->maskEmail($user->email),
            'resendIn' => $otpService->secondsUntilResendAllowed($key),
            'codeLength' => (int) config('gmail.otp_length', 6),
        ]);
    }

    public function verify(Request $request, OtpService $otpService)
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get('login.pending_user_id');
        if (! $userId) {
            return redirect()->route('login')->with('flash.warning', 'Sesi login berakhir. Silakan masuk ulang.');
        }

        $user = User::find($userId);
        if (! $user) {
            $request->session()->forget(['login.pending_user_id', 'login.remember']);

            return redirect()->route('login');
        }

        $key = 'login:'.$user->id;
        [$ok, $error] = $otpService->verify($key, $request->input('code'));

        if (! $ok) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        // Tandai email terverifikasi jika belum
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $remember = (bool) $request->session()->get('login.remember', false);
        $request->session()->forget(['login.pending_user_id', 'login.remember']);

        return $this->completeLogin($request, $user, $remember);
    }

    public function resendVerify(Request $request, OtpService $otpService, OtpMailer $otpMailer)
    {
        $userId = $request->session()->get('login.pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $key = 'login:'.$user->id;

        if (! $otpService->canResend($key)) {
            $wait = $otpService->secondsUntilResendAllowed($key);
            throw ValidationException::withMessages(['code' => "Tunggu {$wait} detik lagi sebelum minta kode baru."]);
        }

        $code = $otpService->generate($key);
        $otpService->markResent($key);

        [$sent, $error, $note] = $otpMailer->send(
            $user->email,
            $user->full_name,
            'Kode Login — '.LibrarySettings::get('library_name', 'Perpustakaan Nusantara'),
            'Gunakan kode berikut untuk menyelesaikan login Anda:',
            $code
        );

        if (! $sent) {
            throw ValidationException::withMessages(['code' => "Gagal mengirim kode. {$error}"]);
        }

        return back()->with('flash.success', $note ?: 'Kode baru sudah dikirim ke email Anda.');
    }

    public function destroy(Request $request)
    {
        if ($user = $request->user()) {
            ActivityLog::record('logout', 'Keluar dari sistem', $user->id);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('flash.info', 'Anda sudah keluar.');
    }

    private function completeLogin(Request $request, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login' => now()])->saveQuietly();
        ActivityLog::record('login', 'Masuk ke sistem', $user->id);

        return redirect()->intended(route('dashboard'))
            ->with('flash.success', 'Selamat datang, '.Str::of($user->full_name)->before(' ').'!');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email) + [1 => ''];
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(1, mb_strlen($local) - 2)).'@'.$domain;
    }
}
