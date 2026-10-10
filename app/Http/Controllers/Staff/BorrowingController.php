<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BorrowingController extends Controller
{
    public function __construct(private BorrowingService $service) {}

    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString() ?: 'requested';

        $query = Borrowing::with(['user', 'book', 'handledBy'])
            ->when($tab === 'requested', fn ($q) => $q->where('status', Borrowing::REQUESTED))
            ->when($tab === 'borrowed', fn ($q) => $q->where('status', Borrowing::BORROWED)->whereNull('return_requested_at'))
            ->when($tab === 'return', fn ($q) => $q->where('status', Borrowing::BORROWED)->whereNotNull('return_requested_at'))
            ->when($tab === 'overdue', fn ($q) => $q->where('status', Borrowing::BORROWED)->where('due_date', '<', now()->toDateString()))
            ->when($tab === 'history', fn ($q) => $q->whereIn('status', [Borrowing::RETURNED, Borrowing::REJECTED, Borrowing::LOST, Borrowing::CANCELLED]))
            ->orderByDesc('updated_at');

        $borrowings = $query->paginate(20)->withQueryString();

        return Inertia::render('Staff/Borrowings/Index', [
            'borrowings' => $borrowings,
            'tab' => $tab,
            'counts' => [
                'requested' => Borrowing::where('status', Borrowing::REQUESTED)->count(),
                'borrowed' => Borrowing::where('status', Borrowing::BORROWED)->whereNull('return_requested_at')->count(),
                'return' => Borrowing::where('status', Borrowing::BORROWED)->whereNotNull('return_requested_at')->count(),
                'overdue' => Borrowing::where('status', Borrowing::BORROWED)->where('due_date', '<', now()->toDateString())->count(),
            ],
        ]);
    }

    public function approve(Request $request, Borrowing $borrowing)
    {
        $data = $request->validate([
            'days' => 'nullable|integer|min:1|max:60',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $this->service->approve(
                $borrowing,
                $request->user(),
                (int) ($data['days'] ?? 7),
                $data['note'] ?? null
            );
            return back()->with('success', 'Peminjaman disetujui.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Borrowing $borrowing)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        try {
            $this->service->reject($borrowing, $request->user(), $data['reason'] ?? null);
            return back()->with('success', 'Permintaan ditolak.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function confirmReturn(Request $request, Borrowing $borrowing)
    {
        try {
            $this->service->confirmReturn($borrowing, $request->user());
            return back()->with('success', 'Pengembalian dikonfirmasi.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectReturn(Request $request, Borrowing $borrowing)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        try {
            $this->service->rejectReturn($borrowing, $request->user(), $data['reason'] ?? null);
            return back()->with('success', 'Pengajuan kembali ditolak (buku belum diterima).');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markLost(Request $request, Borrowing $borrowing)
    {
        try {
            $this->service->markLost($borrowing, $request->user());
            return back()->with('success', 'Buku ditandai hilang. Denda diterapkan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
