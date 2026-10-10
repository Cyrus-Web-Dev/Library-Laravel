<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route berdasarkan peran. Pakai di routes/web.php:
 *   Route::middleware('role:superadmin')->group(...)
 *   Route::middleware('role:superadmin,pustakawan')->group(...)   // staf (OR)
 *
 * Juga menegakkan must_change_password: anggota/staf dengan password sementara
 * dipaksa ke halaman ganti password dulu sebelum mengakses halaman lain.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->status !== 'active') {
            auth()->logout();
            return redirect()->route('login')->with('flash.danger', 'Akun Anda tidak aktif. Hubungi pustakawan.');
        }

        if ($user->must_change_password && $request->routeIs('profile.*') === false) {
            return redirect()->route('profile.edit', ['tab' => 'password'])
                ->with('flash.warning', 'Password Anda masih sementara. Silakan ganti dulu.');
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak punya akses ke halaman itu.');
        }

        return $next($request);
    }
}
