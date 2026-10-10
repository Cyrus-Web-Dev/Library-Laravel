<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

/** Katalog buku publik — tidak butuh token, sama seperti halaman katalog web. */
class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::query()->active()
            ->with(['genre', 'type'])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->integer('genre_id'), fn ($q, $v) => $q->where('genre_id', $v))
            ->when($request->integer('type_id'), fn ($q, $v) => $q->where('type_id', $v))
            ->when($request->boolean('available_only'), fn ($q) => $q->availableToBorrow())
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return BookResource::collection($books);
    }

    public function show(Book $book)
    {
        abort_unless($book->status !== Book::STATUS_ARCHIVED, 404);

        return new BookResource($book->load(['genre', 'type']));
    }
}
