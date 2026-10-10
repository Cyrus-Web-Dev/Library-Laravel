<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Book */
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_code' => $this->book_code,
            'isbn' => $this->isbn,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'genre' => $this->whenLoaded('genre', fn () => [
                'id' => $this->genre?->id,
                'name' => $this->genre?->name,
            ]),
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->type?->id,
                'name' => $this->type?->name,
            ]),
            'description' => $this->description,
            'cover_url' => $this->cover_url,
            'location' => $this->location,
            'total_stock' => $this->total_stock,
            'available_stock' => $this->available_stock,
            'is_borrowable' => $this->isBorrowable(),
            'has_ebook' => (bool) $this->ebook_file,
            'language' => $this->language,
            'status' => $this->status,
            'average_rating' => $this->averageRating(),
            'ratings_count' => $this->ratingsCount(),
            'qr_url' => route('books.scan', $this->id),
        ];
    }
}
