<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Auth API berbasis token (Sanctum), terpisah dari sesi web. Dipakai aplikasi
 * mobile atau integrasi pihak ketiga. Token dibuat per-perangkat ("device_name")
 * sehingga bisa dicabut satu per satu tanpa mengganggu perangkat lain.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $user = User::where('username', $data['login'])->orWhere('email', mb_strtolower($data['login']))->first();
        $passwordOk = Hash::check($data['password'], $user?->password ?? Hash::make('dummy-pemerataan-waktu'));

        if (! $user || ! $passwordOk) {
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }

        if (Hash::needsRehash($user->password)) {
            $user->password = $data['password'];
            $user->save();
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages(['login' => 'Akun ini tidak aktif. Hubungi pustakawan.']);
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;

        $user->forceFill(['last_login' => now()])->saveQuietly();
        ActivityLog::record('api.login', "Login API dari perangkat: {$data['device_name']}", $user->id);

        return response()->json([
            'token' => $token,
            'user' => $this->payload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    /** Cabut semua token milik pengguna (logout dari semua perangkat). */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Berhasil logout dari semua perangkat.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->payload($request->user())]);
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'member_type' => $user->member_type,
            'member_number' => $user->memberNumber(),
        ];
    }
}
