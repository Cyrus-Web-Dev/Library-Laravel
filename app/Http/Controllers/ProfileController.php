<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'tab' => $request->query('tab', 'profile'),
            'user' => $request->user()->only([
                'id', 'full_name', 'username', 'email', 'phone', 'address', 'bio',
                'institution', 'member_code', 'member_type', 'must_change_password',
            ]),
            'memberTypes' => \App\Models\User::MEMBER_TYPES,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bio' => 'nullable|string',
            'institution' => 'nullable|string|max:150',
            'member_code' => 'nullable|string|max:30',
            'member_type' => $user->isAnggota() ? ['required', Rule::in(\App\Models\User::MEMBER_TYPES)] : 'nullable',
        ]);

        $user->update($data);

        return back()->with('flash.success', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.'])->withInput();
        }

        $user->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false])->save();
        ActivityLog::record('change_password', 'Ganti password', $user->id);

        return back()->with('flash.success', 'Password berhasil diganti.');
    }
}
