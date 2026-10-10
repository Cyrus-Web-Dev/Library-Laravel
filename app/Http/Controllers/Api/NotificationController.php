<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->appNotifications()->paginate(20);
    }

    public function markRead(Request $request, int $id)
    {
        $request->user()->appNotifications()->whereKey($id)->update(['is_read' => true]);

        return response()->json(['message' => 'Ditandai dibaca.']);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->appNotifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }
}
