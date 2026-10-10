<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Policies\BookPolicy;
use App\Policies\BorrowingPolicy;
use App\Policies\UserPolicy;
use App\Services\GmailApiService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GmailApiService::class);
    }

    public function boot(): void
    {
        Gate::policy(Book::class, BookPolicy::class);
        Gate::policy(Borrowing::class, BorrowingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Gate sederhana untuk dipakai di controller/Vue (lewat Inertia shared props)
        Gate::define('access-staff-panel', fn (User $u) => $u->isStaff());
        Gate::define('access-admin-panel', fn (User $u) => $u->isSuperadmin());

        // Kebijakan password minimum di seluruh aplikasi (registrasi, reset, ganti password):
        // minimal 8 karakter, campuran huruf besar/kecil, ada angka, dan dicek terhadap daftar
        // password yang pernah bocor (Have I Been Pwned, lewat k-anonymity — tidak mengirim
        // password asli ke layanan luar). Hash aktual tetap argon2id (lihat config/hashing.php).
        Password::defaults(function () {
            $rule = Password::min(8)->mixedCase()->numbers();

            return $this->app->environment('production') ? $rule->uncompromised() : $rule;
        });

        // Driver "gmail_api" untuk Laravel Mail (dipakai MAIL_MAILER=gmail_api).
        Mail::extend('gmail_api', fn () => new GmailApiTransport($this->app->make(GmailApiService::class)));

        // Batas laju default untuk routes/api.php: 60 req/menit per user login, atau per IP bila tamu.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
