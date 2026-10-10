<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RatingController extends Controller
{
    public function store(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($user->isAnggota(), 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Rating::updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'is_approved' => false,
            ]
        );

        return back()->with('success', 'Ulasan dikirim dan menunggu persetujuan pustakawan.');
    }

    public function moderateIndex(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        $ratings = Rating::with(['user', 'book'])
            ->where('is_approved', false)
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Staff/Ratings/Index', [
            'ratings' => $ratings,
        ]);
    }

    public function approve(Request $request, Rating $rating)
    {
        abort_unless($request->user()->isStaff(), 403);
        $rating->update(['is_approved' => true]);
        return back()->with('success', 'Ulasan disetujui.');
    }

    public function reject(Request $request, Rating $rating)
    {
        abort_unless($request->user()->isStaff(), 403);
        $rating->delete();
        return back()->with('success', 'Ulasan ditolak dan dihapus.');
    }
}
