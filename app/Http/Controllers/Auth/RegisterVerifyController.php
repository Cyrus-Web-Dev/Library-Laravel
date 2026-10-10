<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use App\Services\OtpMailer;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisterVerifyController extends Controller
{
    public function create(Request $request, OtpService $otpService): Response|\Illuminate\Http\RedirectResponse
    {
        $pending = $request->session()->get('register.pending');
        if (! $pending) {
            return redirect()->route('register')->with('flash.warning', 'Sesi pendaftaran sudah berakhir. Silakan daftar ulang.');
        }

        return Inertia::render('Auth/RegisterVerify', [
            'maskedEmail' => $this->maskEmail($pending['email']),
            'resendIn' => $otpService->secondsUntilResendAllowed('register:'.$pending['email']),
            'codeLength' => (int) config('gmail.otp_length', 6),
        ]);
    }

    public function store(Request $request, OtpService $otpService)
    {
        $request->validate(['code' => 'required|string']);

        $pending = $request->session()->get('register.pending');
        if (! $pending) {
            return redirect()->route('register')->with('flash.warning', 'Sesi pendaftaran sudah berakhir. Silakan daftar ulang.');
        }

        [$valid, $message] = $otpService->verify('register:'.$pending['email'], $request->input('code'));
        if (! $valid) {
            throw ValidationException::withMessages(['code' => $message]);
        }

        if (User::where('email', $pending['email'])->exists() || User::where('username', $pending['username'])->exists()) {
            $request->session()->forget('register.pending');
            throw ValidationException::withMessages(['code' => 'Username atau email ini sudah terdaftar. Silakan masuk.']);
        }

        $user = DB::transaction(function () use ($pending) {
            $user = User::create([
                'full_name' => $pending['full_name'],
                'username' => $pending['username'],
                'email' => $pending['email'],
                'member_type' => $pending['member_type'],
                'institution' => $pending['institution'],
                'member_code' => $pending['member_code'],
                'phone' => $pending['phone'],
                'role' => User::ROLE_ANGGOTA,
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => 'placeholder-ditimpa-di-bawah',
            ]);

            // Password SUDAH di-hash (argon2id) sejak langkah 1. Ditulis langsung lewat query
            // builder, BUKAN $user->update(), supaya cast "hashed" di model tidak menghash ulang.
            DB::table('users')->where('id', $user->id)->update(['password' => $pending['password']]);

            return $user->fresh();
        });

        $request->session()->forget('register.pending');

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLog::record('register', "Anggota baru mendaftar: {$user->username}");
        Notification::notifyStaff('Anggota baru', "{$user->full_name} ({$user->member_type}) baru mendaftar.");

        return redirect()->route('dashboard')->with('flash.success', 'Akun berhasil dibuat. Selamat datang!');
    }

    public function resend(Request $request, OtpService $otpService, OtpMailer $otpMailer)
    {
        $pending = $request->session()->get('register.pending');
        if (! $pending) {
            return redirect()->route('register')->with('flash.warning', 'Sesi pendaftaran sudah berakhir. Silakan daftar ulang.');
        }

        $key = 'register:'.$pending['email'];
        if (! $otpService->canResend($key)) {
            $wait = $otpService->secondsUntilResendAllowed($key);
            throw ValidationException::withMessages(['code' => "Tunggu {$wait} detik lagi sebelum minta kode baru."]);
        }

        $code = $otpService->generate($key);
        $otpService->markResent($key);

        [$sent, $error, $note] = $otpMailer->send(
            $pending['email'],
            $pending['full_name'],
            'Verifikasi Email Pendaftaran — Perpustakaan Nusantara',
            'Gunakan kode berikut untuk memverifikasi email pendaftaran Anda:',
            $code
        );

        if (! $sent) {
            throw ValidationException::withMessages(['code' => "Gagal mengirim kode. {$error}"]);
        }

        return back()->with('flash.success', $note ?: 'Kode baru sudah dikirim ke email Anda.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email) + [1 => ''];
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(1, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
