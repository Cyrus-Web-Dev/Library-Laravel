<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_member_can_login_with_username_or_email(): void
    {
        User::factory()->create(['username' => 'dewi', 'email' => 'dewi@example.com', 'password' => Hash::make('Rahasia123')]);

        $this->post(route('login'), ['login' => 'dewi', 'password' => 'Rahasia123'])->assertRedirect(route('dashboard'));
        $this->post(route('logout'));
        $this->post(route('login'), ['login' => 'dewi@example.com', 'password' => 'Rahasia123'])->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function wrong_password_is_rejected(): void
    {
        User::factory()->create(['username' => 'dewi', 'password' => Hash::make('Rahasia123')]);

        $response = $this->post(route('login'), ['login' => 'dewi', 'password' => 'salah']);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    #[Test]
    public function inactive_account_cannot_login(): void
    {
        User::factory()->create(['username' => 'dewi', 'password' => Hash::make('Rahasia123'), 'status' => 'banned']);

        $response = $this->post(route('login'), ['login' => 'dewi', 'password' => 'Rahasia123']);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    #[Test]
    public function login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['username' => 'dewi', 'password' => Hash::make('Rahasia123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['login' => 'dewi', 'password' => 'salah']);
        }
        $response = $this->post(route('login'), ['login' => 'dewi', 'password' => 'Rahasia123']);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('login'));
        $this->assertGuest();
    }

    #[Test]
    public function an_old_bcrypt_password_is_upgraded_to_argon2id_on_successful_login(): void
    {
        $user = User::factory()->create(['username' => 'dewi']);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['password' => password_hash('Rahasia123', PASSWORD_BCRYPT)]);

        $this->post(route('login'), ['login' => 'dewi', 'password' => 'Rahasia123'])->assertRedirect(route('dashboard'));

        $this->assertStringStartsWith('$argon2id$', $user->fresh()->password);
    }

    #[Test]
    public function staff_with_temporary_password_is_redirected_to_change_password(): void
    {
        $staff = User::factory()->pustakawan()->create(['must_change_password' => true]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertRedirect(route('profile.edit', ['tab' => 'password']));
    }
}
