<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordHashingTest extends TestCase
{
    #[Test]
    public function password_hash_driver_is_argon2id(): void
    {
        $this->assertSame('argon2id', config('hashing.driver'));
    }

    #[Test]
    public function hash_make_produces_an_argon2id_hash(): void
    {
        $hash = Hash::make('Rahasia123');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hash::check('Rahasia123', $hash));
        $this->assertFalse(Hash::check('salah', $hash));
    }

    #[Test]
    public function an_old_bcrypt_hash_is_detected_as_needing_rehash(): void
    {
        $bcryptHash = password_hash('Rahasia123', PASSWORD_BCRYPT);

        $this->assertTrue(Hash::needsRehash($bcryptHash));
    }
}
