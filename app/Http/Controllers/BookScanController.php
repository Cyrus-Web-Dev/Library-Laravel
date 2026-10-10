<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman hasil scan QR buku — publik, tanpa login, persis seperti scan.php
 * di versi PHP native. Menampilkan judul, genre, tipe, lokasi rak, dan stok.
 */
class BookScanController extends Controller
{
    public function show(Book $book): Response
    {
        abort_unless($book->status !== Book::STATUS_ARCHIVED, 404);

        $book->load(['genre', 'type']);

        return Inertia::render('Books/Scan', [
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'genre' => $book->genre?->name,
                'type' => $book->type?->name,
                'location' => $book->location,
                'book_code' => $book->book_code,
                'available_stock' => $book->available_stock,
                'total_stock' => $book->total_stock,
                'is_borrowable' => $book->isBorrowable(),
                'cover_url' => $book->cover_image ? asset('storage/covers/'.$book->cover_image) : null,
                'average_rating' => $book->averageRating(),
                'ratings_count' => $book->ratingsCount(),
            ],
        ]);
    }
}
