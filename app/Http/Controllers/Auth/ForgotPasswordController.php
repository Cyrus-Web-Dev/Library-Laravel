<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpMailer;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lupa password — langkah 1: cari akun lewat username/email, kirim kode OTP ke
 * email akun tersebut. Langkah 2 ada di ResetPasswordController.
 *
 * Supaya email seseorang tidak bisa dipakai menebak akun mana yang terdaftar,
 * respons sukses ditampilkan sama saja baik akun ditemukan maupun tidak — tapi
 * OTP hanya benar-benar dikirim bila akun memang ada.
 */
class ForgotPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Forgot');
    }

    public function store(Request $request, OtpService $otpService, OtpMailer $otpMailer)
    {
        $request->validate(['login' => 'required|string']);

        $login = $request->input('login');
        $user = User::where('username', $login)->orWhere('email', mb_strtolower($login))->where('status', 'active')->first();

        if ($user) {
            $key = 'reset:'.$user->id;
            if ($otpService->canResend($key)) {
                $code = $otpService->generate($key);
                $otpService->markResent($key);
                $otpMailer->send($user->email, $user->full_name, 'Reset Password — Perpustakaan Digital',
                    'Gunakan kode berikut untuk mengatur ulang password akun Anda. Abaikan email ini bila Anda tidak meminta reset password.', $code);
            }
            $request->session()->put('reset.user_id', $user->id);
        }

        // Pesan selalu sama supaya tidak membocorkan akun mana yang terdaftar.
        return redirect()->route('password.verify')
            ->with('flash.info', 'Jika akun ditemukan, kode verifikasi sudah dikirim ke email yang terdaftar.');
    }
}
