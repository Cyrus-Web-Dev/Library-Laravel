<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegisterVerifyController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BookScanController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\Staff\BookController as StaffBookController;
use App\Http\Controllers\Staff\BorrowingController as StaffBorrowingController;
use App\Http\Controllers\Staff\QrLabelController;
use App\Http\Controllers\Staff\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/katalog/{book}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/books/{book}/scan', [BookScanController::class, 'show'])->name('books.scan');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/register/verify', [RegisterVerifyController::class, 'create'])->name('register.verify');
    Route::post('/register/verify', [RegisterVerifyController::class, 'store']);
    Route::post('/register/verify/resend', [RegisterVerifyController::class, 'resend'])->name('register.verify.resend');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/login/verify', [AuthenticatedSessionController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [AuthenticatedSessionController::class, 'verify']);
    Route::post('/login/verify/resend', [AuthenticatedSessionController::class, 'resendVerify'])->name('login.verify.resend');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    Route::get('/reset-password', [ResetPasswordController::class, 'create'])->name('password.verify');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
    Route::post('/reset-password/resend', [ResetPasswordController::class, 'resend'])->name('password.verify.resend');
});

Route::middleware(['auth', 'role'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::post('/pinjam/{book}', function (\App\Models\Book $book) {
        $user = auth()->user();
        abort_unless($user->isAnggota(), 403);
        try {
            app(\App\Services\BorrowingService::class)->requestBorrow($user, $book, request('note'));
            return back()->with('success', 'Permintaan peminjaman berhasil diajukan.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    })->name('borrowings.request');

    Route::post('/katalog/{book}/rating', [RatingController::class, 'store'])->name('ratings.store');

    // Staff
    Route::middleware('can:access-staff-panel')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/books', [StaffBookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [StaffBookController::class, 'create'])->name('books.create');
        Route::post('/books', [StaffBookController::class, 'store'])->name('books.store');
        Route::get('/books/{book}/edit', [StaffBookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{book}', [StaffBookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [StaffBookController::class, 'destroy'])->name('books.destroy');

        Route::get('/borrowings', [StaffBorrowingController::class, 'index'])->name('borrowings.index');
        Route::post('/borrowings/{borrowing}/approve', [StaffBorrowingController::class, 'approve'])->name('borrowings.approve');
        Route::post('/borrowings/{borrowing}/reject', [StaffBorrowingController::class, 'reject'])->name('borrowings.reject');
        Route::post('/borrowings/{borrowing}/confirm-return', [StaffBorrowingController::class, 'confirmReturn'])->name('borrowings.confirm-return');
        Route::post('/borrowings/{borrowing}/reject-return', [StaffBorrowingController::class, 'rejectReturn'])->name('borrowings.reject-return');
        Route::post('/borrowings/{borrowing}/lost', [StaffBorrowingController::class, 'markLost'])->name('borrowings.lost');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/qr-labels', [QrLabelController::class, 'index'])->name('qr.index');
        Route::post('/qr-labels/print', [QrLabelController::class, 'print'])->name('qr.print');

        Route::get('/ratings', [RatingController::class, 'moderateIndex'])->name('ratings.index');
        Route::post('/ratings/{rating}/approve', [RatingController::class, 'approve'])->name('ratings.approve');
        Route::delete('/ratings/{rating}', [RatingController::class, 'reject'])->name('ratings.reject');
    });

    // Super Admin
    Route::middleware('can:access-admin-panel')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::put('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup', [BackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
    });
});
