<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpMailer;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pendaftaran anggota — dua langkah:
 *   1. (controller ini) Validasi data & password, simpan sementara di session (password
 *      SUDAH di-hash di sini, argon2id), kirim kode OTP ke email.
 *   2. RegisterVerifyController: cocokkan OTP, baru akun benar-benar dibuat.
 * Akun yang dibuat lewat jalur ini selalu berperan "anggota" — staf hanya dibuat oleh
 * Super Admin (lihat Admin\UserController) atau lewat pengajuan jadi pustakawan.
 */
class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'memberTypes' => User::MEMBER_TYPES,
        ]);
    }

    public function store(Request $request, OtpService $otpService, OtpMailer $otpMailer)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')],
            'member_type' => ['required', Rule::in(User::MEMBER_TYPES)],
            'institution' => 'nullable|string|max:150',
            'member_code' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'full_name' => 'nama lengkap', 'username' => 'username', 'email' => 'email',
            'member_type' => 'jenis anggota', 'password' => 'password',
        ]);

        $email = mb_strtolower($data['email']);
        $key = 'register:'.$email;

        if (! $otpService->canResend($key)) {
            $wait = $otpService->secondsUntilResendAllowed($key);
            throw ValidationException::withMessages(['email' => "Kode verifikasi baru saja dikirim. Tunggu {$wait} detik untuk kirim ulang."]);
        }

        $request->session()->put('register.pending', [
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'email' => $email,
            'member_type' => $data['member_type'],
            'institution' => $data['institution'] ?? null,
            'member_code' => $data['member_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']), // argon2id, lihat config/hashing.php
        ]);

        $code = $otpService->generate($key);
        $otpService->markResent($key);

        [$sent, $error, $note] = $otpMailer->send(
            $email,
            $data['full_name'],
            'Verifikasi Email Pendaftaran — Perpustakaan Nusantara',
            'Gunakan kode berikut untuk memverifikasi email pendaftaran Anda:',
            $code
        );

        if (! $sent) {
            throw ValidationException::withMessages(['email' => "Gagal mengirim kode verifikasi. {$error}"]);
        }

        return redirect()->route('register.verify')->with('flash.info', $note ?: 'Kode verifikasi sudah dikirim ke email Anda.');
    }
}
