<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LibrarySettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'library_name' => LibrarySettings::get('library_name', 'Perpustakaan Nusantara'),
                'max_books_per_user' => LibrarySettings::get('max_books_per_user', '3'),
                // seeder & service memakai max_borrow_days
                'loan_days' => LibrarySettings::get('max_borrow_days', LibrarySettings::get('loan_days', '7')),
                'fine_per_day' => LibrarySettings::get('fine_per_day', '1000'),
                // seeder & model memakai fine_max
                'max_fine' => LibrarySettings::get('fine_max', LibrarySettings::get('max_fine', '20000')),
                'block_if_fine' => LibrarySettings::get('block_if_fine', '1'),
                'qr_base_url' => LibrarySettings::get('qr_base_url', ''),
                'login_otp' => LibrarySettings::get('login_otp', '1'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $data = $request->validate([
            'library_name' => 'required|string|max:100',
            'max_books_per_user' => 'required|integer|min:1|max:20',
            'loan_days' => 'required|integer|min:1|max:60',
            'fine_per_day' => 'required|numeric|min:0',
            'max_fine' => 'required|numeric|min:0',
            'block_if_fine' => 'required|in:0,1',
            'qr_base_url' => 'nullable|string|max:255',
            'login_otp' => 'required|in:0,1',
        ]);

        // Simpan dengan kunci yang konsisten dipakai service & model
        LibrarySettings::set('library_name', (string) $data['library_name']);
        LibrarySettings::set('max_books_per_user', (string) $data['max_books_per_user']);
        LibrarySettings::set('max_borrow_days', (string) $data['loan_days']);
        LibrarySettings::set('loan_days', (string) $data['loan_days']); // alias
        LibrarySettings::set('fine_per_day', (string) $data['fine_per_day']);
        LibrarySettings::set('fine_max', (string) $data['max_fine']);
        LibrarySettings::set('max_fine', (string) $data['max_fine']); // alias
        LibrarySettings::set('block_if_fine', (string) $data['block_if_fine']);
        LibrarySettings::set('qr_base_url', (string) ($data['qr_base_url'] ?? ''));
        LibrarySettings::set('login_otp', (string) $data['login_otp']);

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
