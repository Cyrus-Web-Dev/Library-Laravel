<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        $months = collect(range(5, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'key' => $date->format('Y-m'),
                'label' => $date->translatedFormat('M Y'),
            ];
        });

        $borrowingsByMonth = Borrowing::query()
            ->select(DB::raw("to_char(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $returnsByMonth = Borrowing::query()
            ->select(DB::raw("to_char(return_date, 'YYYY-MM') as month"), DB::raw('count(*) as total'))
            ->whereNotNull('return_date')
            ->where('return_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $chartLabels = $months->pluck('label')->values();
        $chartBorrow = $months->map(fn ($m) => (int) ($borrowingsByMonth[$m['key']] ?? 0))->values();
        $chartReturn = $months->map(fn ($m) => (int) ($returnsByMonth[$m['key']] ?? 0))->values();

        $statusCounts = Borrowing::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $topBooks = Book::query()
            ->withCount(['borrowings' => fn ($q) => $q->whereIn('status', ['borrowed', 'returned', 'lost'])])
            ->orderByDesc('borrowings_count')
            ->limit(8)
            ->get(['id', 'title', 'author', 'book_code']);

        $genreStats = Book::query()
            ->join('genres', 'books.genre_id', '=', 'genres.id')
            ->select('genres.name', DB::raw('count(books.id) as total'))
            ->groupBy('genres.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return Inertia::render('Staff/Reports/Index', [
            'stats' => [
                'total_books' => Book::where('status', '!=', 'archived')->count(),
                'total_members' => User::where('role', User::ROLE_ANGGOTA)->count(),
                'active_loans' => Borrowing::where('status', 'borrowed')->count(),
                'overdue' => Borrowing::where('status', 'borrowed')->where('due_date', '<', now()->toDateString())->count(),
                'pending_requests' => Borrowing::where('status', 'requested')->count(),
                'total_fines' => (float) Borrowing::where('fine_amount', '>', 0)->where('fine_paid', false)->sum('fine_amount'),
            ],
            'chart' => [
                'labels' => $chartLabels,
                'borrowings' => $chartBorrow,
                'returns' => $chartReturn,
            ],
            'statusCounts' => $statusCounts,
            'topBooks' => $topBooks,
            'genreStats' => $genreStats,
        ]);
    }
}
