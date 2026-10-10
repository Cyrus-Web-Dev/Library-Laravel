<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $users = User::query()
            ->when($request->string('q')->toString(), function ($q, $term) {
                $q->where(function ($w) use ($term) {
                    $w->where('full_name', 'ilike', "%{$term}%")
                        ->orWhere('username', 'ilike', "%{$term}%")
                        ->orWhere('email', 'ilike', "%{$term}%")
                        ->orWhere('member_code', 'ilike', "%{$term}%");
                });
            })
            ->when($request->string('role')->toString(), fn ($q, $v) => $q->where('role', $v))
            ->when($request->string('status')->toString(), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['q', 'role', 'status']),
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        abort_if($user->id === $request->user()->id, 403, 'Tidak bisa mengubah peran sendiri.');

        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_PUSTAKAWAN, User::ROLE_ANGGOTA])],
        ]);

        $user->update(['role' => $data['role']]);

        return back()->with('success', 'Peran pengguna diperbarui.');
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        abort_if($user->id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'pending'])],
        ]);

        $user->update(['status' => $data['status']]);

        return back()->with('success', 'Status pengguna diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $temp = 'Temp' . random_int(1000, 9999) . '!';
        $user->update([
            'password' => Hash::make($temp),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password sementara: {$temp} — minta pengguna segera mengganti.");
    }
}
