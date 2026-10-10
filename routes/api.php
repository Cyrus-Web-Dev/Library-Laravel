<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookRequestController;
use App\Http\Controllers\Api\BorrowingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API (v1) — token-based lewat Laravel Sanctum, terpisah dari sesi web.
| Ditujukan untuk aplikasi mobile atau integrasi pihak ketiga.
| Base URL: /api/v1/...   Endpoint yang butuh login mengirim header:
|   Authorization: Bearer <token>
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->name('api.')->group(function () {

    // Login: 5 percobaan/menit per IP supaya tidak bisa dipakai brute-force.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');

    // Katalog publik — bisa diakses tanpa token, sama seperti halaman katalog web.
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/books/{book}/ratings', [RatingController::class, 'index'])->name('books.ratings.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        // Peminjaman (self-service anggota; persetujuan tetap lewat panel staf di web)
        Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
        Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
        Route::post('/borrowings/{borrowing}/cancel', [BorrowingController::class, 'cancel'])->name('borrowings.cancel');
        Route::post('/borrowings/{borrowing}/request-return', [BorrowingController::class, 'requestReturn'])->name('borrowings.request-return');
        Route::post('/borrowings/{borrowing}/cancel-return', [BorrowingController::class, 'cancelReturn'])->name('borrowings.cancel-return');

        // Ulasan
        Route::post('/books/{book}/ratings', [RatingController::class, 'store'])->name('books.ratings.store');
        Route::delete('/books/{book}/ratings', [RatingController::class, 'destroy'])->name('books.ratings.destroy');

        // Request buku
        Route::get('/book-requests', [BookRequestController::class, 'index'])->name('book-requests.index');
        Route::post('/book-requests', [BookRequestController::class, 'store'])->name('book-requests.store');
        Route::delete('/book-requests/{bookRequest}', [BookRequestController::class, 'destroy'])->name('book-requests.destroy');

        // Notifikasi
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    });
});
