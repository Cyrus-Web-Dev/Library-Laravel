<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);

        $books = Book::query()
            ->with(['genre', 'type'])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->integer('genre_id'), fn ($q, $v) => $q->where('genre_id', $v))
            ->when($request->integer('type_id'), fn ($q, $v) => $q->where('type_id', $v))
            ->when($request->string('status')->toString(), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Staff/Books/Index', [
            'books' => $books,
            'filters' => $request->only(['q', 'genre_id', 'type_id', 'status']),
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'types' => BookType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Book::class);

        return Inertia::render('Staff/Books/Form', [
            'book' => null,
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'types' => BookType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['available_stock'] = $data['total_stock'];

        if ($request->hasFile('cover')) {
            $data['cover_image'] = $request->file('cover')->store('covers', 'public');
        }
        if ($request->hasFile('ebook')) {
            $data['ebook_file'] = $request->file('ebook')->store('ebooks', 'local');
        }

        Book::create($data);

        return redirect()->route('staff.books.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        return Inertia::render('Staff/Books/Form', [
            'book' => $book->load(['genre', 'type']),
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'types' => BookType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $data = $this->validated($request, $book);

        // Adjust available_stock if total_stock changed
        if (isset($data['total_stock']) && $data['total_stock'] != $book->total_stock) {
            $diff = $data['total_stock'] - $book->total_stock;
            $data['available_stock'] = max(0, $book->available_stock + $diff);
        }

        if ($request->hasFile('cover')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = $request->file('cover')->store('covers', 'public');
        }
        if ($request->hasFile('ebook')) {
            if ($book->ebook_file) {
                Storage::disk('local')->delete($book->ebook_file);
            }
            $data['ebook_file'] = $request->file('ebook')->store('ebooks', 'local');
        }

        $book->update($data);

        return redirect()->route('staff.books.index')->with('success', 'Buku berhasil diperbarui.');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        // Soft archive instead of hard delete if has history
        if ($book->borrowings()->exists()) {
            $book->update(['status' => Book::STATUS_ARCHIVED, 'available_stock' => 0]);
            return back()->with('success', 'Buku diarsipkan karena memiliki riwayat peminjaman.');
        }

        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        if ($book->ebook_file) {
            Storage::disk('local')->delete($book->ebook_file);
        }
        $book->delete();

        return back()->with('success', 'Buku berhasil dihapus.');
    }

    private function validated(Request $request, ?Book $book = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($book?->id)],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
            'genre_id' => ['nullable', 'exists:genres,id'],
            'type_id' => ['nullable', 'exists:book_types,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:100'],
            'total_stock' => ['required', 'integer', 'min:0', 'max:9999'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'language' => ['nullable', 'string', 'max:50'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in([Book::STATUS_AVAILABLE, Book::STATUS_UNAVAILABLE, Book::STATUS_ARCHIVED])],
            'cover' => ['nullable', 'image', 'max:2048'],
            'ebook' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);
    }
}
