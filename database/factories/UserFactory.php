<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'role' => User::ROLE_ANGGOTA,
            'member_type' => fake()->randomElement(User::MEMBER_TYPES),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function superadmin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_SUPERADMIN]);
    }

    public function pustakawan(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_PUSTAKAWAN]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
