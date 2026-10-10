<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookType;
use App\Models\Genre;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::query()
            ->active()
            ->with(['genre', 'type'])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->integer('genre_id'), fn ($q, $v) => $q->where('genre_id', $v))
            ->when($request->integer('type_id'), fn ($q, $v) => $q->where('type_id', $v))
            ->when($request->boolean('available_only'), fn ($q) => $q->availableToBorrow())
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        // Append computed for Inertia
        $books->getCollection()->transform(function ($book) {
            $book->cover_url = $book->cover_url;
            $book->average_rating = $book->averageRating();
            $book->ratings_count = $book->ratingsCount();
            return $book;
        });

        return Inertia::render('Catalog/Index', [
            'books' => $books,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'genre_id' => $request->integer('genre_id') ?: null,
                'type_id' => $request->integer('type_id') ?: null,
                'available_only' => $request->boolean('available_only'),
            ],
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'types' => BookType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Book $book)
    {
        abort_unless($book->status !== Book::STATUS_ARCHIVED, 404);

        $book->load(['genre', 'type', 'ratings' => fn ($q) => $q->where('is_approved', true)->with('user:id,full_name')]);
        $book->average_rating = $book->averageRating();
        $book->ratings_count = $book->ratingsCount();
        $book->cover_url = $book->cover_url;

        $user = auth()->user();

        return Inertia::render('Catalog/Show', [
            'book' => $book,
            'canBorrow' => $user && $user->isAnggota(),
            'canRate' => $user && $user->isAnggota(),
        ]);
    }
}
