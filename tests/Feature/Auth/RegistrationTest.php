<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'full_name' => 'Budi Santoso',
        'username' => 'budisan',
        'email' => 'budi@example.com',
        'member_type' => 'Mahasiswa',
        'institution' => 'Universitas Andalas',
        'member_code' => 'NIM-001',
        'phone' => '08123456789',
        'password' => 'Rahasia123',
        'password_confirmation' => 'Rahasia123',
    ];

    #[Test]
    public function step_one_stashes_pending_data_and_does_not_create_the_user_yet(): void
    {
        $response = $this->post(route('register'), $this->payload);

        $response->assertRedirect(route('register.verify'));
        $this->assertDatabaseMissing('users', ['username' => 'budisan']);
        $this->assertNotNull(session('register.pending'));
    }

    #[Test]
    public function weak_password_is_rejected(): void
    {
        $response = $this->post(route('register'), [...$this->payload, 'password' => 'lemah', 'password_confirmation' => 'lemah']);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'budisan']);
    }

    #[Test]
    public function correct_otp_creates_the_member_account_with_argon2id_password(): void
    {
        $this->post(route('register'), $this->payload);
        $code = \Illuminate\Support\Facades\Cache::get('otp:register:budi@example.com:code');

        $response = $this->post(route('register.verify'), ['code' => $code]);

        $response->assertRedirect(route('dashboard'));
        $user = User::where('username', 'budisan')->firstOrFail();
        $this->assertSame(User::ROLE_ANGGOTA, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertStringStartsWith('$argon2id$', $user->password);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function wrong_otp_does_not_create_the_account(): void
    {
        $this->post(route('register'), $this->payload);

        $response = $this->post(route('register.verify'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('users', ['username' => 'budisan']);
    }

    #[Test]
    public function otp_is_locked_after_too_many_wrong_attempts(): void
    {
        $this->post(route('register'), $this->payload);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register.verify'), ['code' => '000000']);
        }
        $response = $this->post(route('register.verify'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('code'));
    }

    #[Test]
    public function resend_is_blocked_during_cooldown(): void
    {
        $this->post(route('register'), $this->payload);

        $response = $this->post(route('register.verify.resend'));

        $response->assertSessionHasErrors('code');
    }
}
