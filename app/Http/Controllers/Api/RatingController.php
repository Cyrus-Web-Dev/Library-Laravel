<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index(Book $book)
    {
        $ratings = $book->approvedRatings()->with('user:id,full_name,avatar')->latest('updated_at')->paginate(20);

        return response()->json($ratings->through(fn (Rating $r) => [
            'id' => $r->id,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'user' => ['full_name' => $r->user->full_name],
            'updated_at' => $r->updated_at->toIso8601String(),
        ]));
    }

    public function store(Request $request, Book $book)
    {
        abort_unless($request->user()->isAnggota(), 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $book->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'is_approved' => true]
        );

        return response()->json(['message' => 'Ulasan tersimpan.', 'rating' => $rating]);
    }

    public function destroy(Request $request, Book $book)
    {
        Rating::where('user_id', $request->user()->id)->where('book_id', $book->id)->delete();

        return response()->json(['message' => 'Ulasan dihapus.']);
    }
}
