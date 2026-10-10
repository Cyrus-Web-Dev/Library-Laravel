<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowingResource;
use App\Models\Book;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Peminjaman lewat API — selalu atas nama pengguna yang sedang login (token),
 * sehingga seorang anggota tidak bisa mengajukan atas nama orang lain.
 * Persetujuan/penolakan tetap hanya lewat panel staf (web), bukan lewat API ini.
 */
class BorrowingController extends Controller
{
    public function __construct(private readonly BorrowingService $service)
    {
    }

    public function index(Request $request)
    {
        // Tidak perlu policy terpisah: query sudah dibatasi ke milik pengguna yang login.
        $borrowings = $request->user()->borrowings()
            ->with('book')
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10);

        return BorrowingResource::collection($borrowings);
    }

    public function store(Request $request)
    {
        $this->authorize('requestAsMember', Borrowing::class);

        $data = $request->validate([
            'book_id' => 'required|exists:books,id',
            'note' => 'nullable|string|max:250',
        ]);

        try {
            $borrowing = $this->service->requestBorrow($request->user(), Book::findOrFail($data['book_id']), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new BorrowingResource($borrowing->load('book'));
    }

    public function cancel(Request $request, Borrowing $borrowing)
    {
        $this->authorize('actAsOwner', $borrowing);

        try {
            $this->service->cancelRequest($borrowing);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Permintaan dibatalkan.']);
    }

    public function requestReturn(Request $request, Borrowing $borrowing)
    {
        $this->authorize('actAsOwner', $borrowing);

        try {
            $this->service->requestReturn($borrowing);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Pengajuan pengembalian terkirim.']);
    }

    public function cancelReturn(Request $request, Borrowing $borrowing)
    {
        $this->authorize('actAsOwner', $borrowing);

        try {
            $this->service->cancelReturnRequest($borrowing);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Pengajuan pengembalian dibatalkan.']);
    }
}
