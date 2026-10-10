<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookRequest;
use App\Models\Notification;
use Illuminate\Http\Request;

class BookRequestController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->bookRequests()->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAnggota(), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:150',
            'genre' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'publisher' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $pendingCount = $request->user()->bookRequests()->where('status', BookRequest::PENDING)->count();
        if ($pendingCount >= 5) {
            return response()->json(['message' => 'Anda masih punya 5 request yang menunggu. Tunggu sebagian diproses dulu.'], 422);
        }

        $bookRequest = $request->user()->bookRequests()->create($data);

        Notification::notifyStaff('Request buku baru', "{$request->user()->full_name} meminta buku \"{$data['title']}\".");

        return response()->json($bookRequest, 201);
    }

    public function destroy(Request $request, BookRequest $bookRequest)
    {
        abort_unless($bookRequest->user_id === $request->user()->id && $bookRequest->status === BookRequest::PENDING, 403);
        $bookRequest->delete();

        return response()->json(['message' => 'Request dibatalkan.']);
    }
}
