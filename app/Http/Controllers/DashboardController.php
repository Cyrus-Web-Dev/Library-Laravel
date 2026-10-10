<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return Inertia::render('Dashboard/Staff', [
                'pendingBorrowCount' => Borrowing::where('status', 'requested')->count(),
                'pendingReturnCount' => Borrowing::where('status', 'borrowed')->whereNotNull('return_requested_at')->count(),
                'overdueCount' => Borrowing::where('status', 'borrowed')->where('due_date', '<', now()->toDateString())->count(),
            ]);
        }

        $active = $user->borrowings()->with('book')->whereIn('status', ['requested', 'borrowed'])->latest()->get();

        return Inertia::render('Dashboard/Member', [
            'active' => \App\Http\Resources\BorrowingResource::collection($active),
        ]);
    }
}
