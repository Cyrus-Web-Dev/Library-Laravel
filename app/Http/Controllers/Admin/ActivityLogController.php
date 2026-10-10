<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $logs = ActivityLog::with('user')
            ->when($request->string('q')->toString(), function ($q, $term) {
                $q->where('action', 'ilike', "%{$term}%")
                    ->orWhere('description', 'ilike', "%{$term}%");
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/ActivityLog', [
            'logs' => $logs,
            'filters' => $request->only('q'),
        ]);
    }
}
