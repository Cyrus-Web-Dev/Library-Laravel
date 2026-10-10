<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\LibrarySettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrLabelController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        $books = Book::query()
            ->active()
            ->when($request->string('q')->toString(), fn ($q, $t) => $q->search($t))
            ->orderBy('title')
            ->paginate(40)
            ->withQueryString();

        return Inertia::render('Staff/QrLabels/Index', [
            'books' => $books,
            'filters' => $request->only('q'),
            'qrBase' => LibrarySettings::get('qr_base_url', url('/')),
        ]);
    }

    public function print(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        $ids = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer|exists:books,id'])['ids'];
        $books = Book::whereIn('id', $ids)->get();
        $base = rtrim(LibrarySettings::get('qr_base_url', url('/')), '/');

        $labels = $books->map(function (Book $book) use ($base) {
            $url = $base . '/books/' . $book->id . '/scan';
            $svg = QrCode::format('svg')->size(140)->margin(1)->generate($url);
            return [
                'id' => $book->id,
                'title' => $book->title,
                'code' => $book->book_code,
                'author' => $book->author,
                'qr_svg' => $svg,
            ];
        });

        return Inertia::render('Staff/QrLabels/Print', [
            'labels' => $labels,
        ]);
    }
}
