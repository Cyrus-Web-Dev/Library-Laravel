<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpMailer;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    public function create(Request $request, OtpService $otpService): Response
    {
        $userId = $request->session()->get('reset.user_id');

        return Inertia::render('Auth/ResetVerify', [
            'resendIn' => $userId ? $otpService->secondsUntilResendAllowed('reset:'.$userId) : 0,
            'codeLength' => (int) config('gmail.otp_length', 6),
        ]);
    }

    public function store(Request $request, OtpService $otpService)
    {
        $data = $request->validate([
            'code' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $userId = $request->session()->get('reset.user_id');

        // Tidak ada sesi reset (login tidak ditemukan di langkah 1, atau sudah kedaluwarsa):
        // tampilkan pesan generik yang sama seperti kode salah, supaya tidak membocorkan
        // apakah akunnya ada.
        if (! $userId) {
            throw ValidationException::withMessages(['code' => 'Kode sudah kedaluwarsa. Silakan ulangi dari "Lupa password".']);
        }

        [$valid, $message] = $otpService->verify('reset:'.$userId, $data['code']);
        if (! $valid) {
            throw ValidationException::withMessages(['code' => $message]);
        }

        $user = User::findOrFail($userId);
        $user->forceFill([
            'password' => Hash::make($data['password']), // argon2id
            'must_change_password' => false,
        ])->save();

        $request->session()->forget('reset.user_id');
        ActivityLog::record('reset_password', 'Password diatur ulang lewat email', $user->id);

        return redirect()->route('login')->with('flash.success', 'Password berhasil diubah. Silakan masuk.');
    }

    public function resend(Request $request, OtpService $otpService, OtpMailer $otpMailer)
    {
        $userId = $request->session()->get('reset.user_id');
        if (! $userId) {
            return redirect()->route('password.request')->with('flash.warning', 'Sesi reset sudah berakhir. Silakan ulangi.');
        }

        $key = 'reset:'.$userId;
        if (! $otpService->canResend($key)) {
            $wait = $otpService->secondsUntilResendAllowed($key);
            throw ValidationException::withMessages(['code' => "Tunggu {$wait} detik lagi sebelum minta kode baru."]);
        }

        $user = User::findOrFail($userId);
        $code = $otpService->generate($key);
        $otpService->markResent($key);

        [$sent, $error, $note] = $otpMailer->send($user->email, $user->full_name, 'Reset Password — Perpustakaan Digital',
            'Gunakan kode berikut untuk mengatur ulang password akun Anda.', $code);

        if (! $sent) {
            throw ValidationException::withMessages(['code' => "Gagal mengirim kode. {$error}"]);
        }

        return back()->with('flash.success', $note ?: 'Kode baru sudah dikirim ke email Anda.');
    }
}
