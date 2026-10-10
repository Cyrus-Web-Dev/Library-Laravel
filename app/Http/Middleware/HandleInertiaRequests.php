<?php

namespace App\Http\Middleware;

use App\Services\LibrarySettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->roleLabel(),
                    'role_short' => method_exists($user, 'roleShort') ? $user->roleShort() : $user->role,
                    'member_type' => $user->member_type,
                    'avatar' => $user->avatar,
                    'initials' => method_exists($user, 'initials') ? $user->initials() : strtoupper(substr($user->full_name ?? 'U', 0, 2)),
                    'must_change_password' => (bool) $user->must_change_password,
                    'is_staff' => $user->isStaff(),
                    'is_superadmin' => $user->isSuperadmin(),
                    'unread_notifications' => method_exists($user, 'appNotifications')
                        ? $user->appNotifications()->where('is_read', false)->count()
                        : 0,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success') ?? $request->session()->get('flash.success'),
                'error' => fn () => $request->session()->get('error') ?? $request->session()->get('flash.danger'),
                'warning' => fn () => $request->session()->get('warning') ?? $request->session()->get('flash.warning'),
                'info' => fn () => $request->session()->get('info') ?? $request->session()->get('flash.info'),
            ],
            'library' => [
                'name' => LibrarySettings::get('library_name', config('app.name')),
                'registration_open' => LibrarySettings::get('registration_open', '1') === '1',
            ],
        ];
    }
}
