<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('full_name', 100);
            $table->string('role', 20)->default('anggota'); // superadmin | pustakawan | anggota
            $table->string('member_type', 20)->default('Umum'); // Siswa | Mahasiswa | Guru | Umum
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 1)->nullable(); // L | P
            $table->text('education')->nullable();
            $table->string('institution', 150)->nullable();
            $table->string('member_code', 30)->nullable();
            $table->string('social_media')->nullable();
            $table->string('sec_question', 150)->nullable();
            $table->string('sec_answer')->nullable(); // hash jawaban pertanyaan keamanan
            $table->boolean('must_change_password')->default(false);
            $table->string('status', 20)->default('active'); // active | inactive | banned
            $table->rememberToken();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();

            $table->index('role');
            $table->index('status');
            $table->index('member_code');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('superadmin','pustakawan','anggota'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active','inactive','banned'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_gender_check CHECK (gender IS NULL OR gender IN ('L','P'))");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Reset password lewat pertanyaan keamanan (aplikasi offline, tanpa email) — token sekali pakai, 15 menit.
        Schema::create('security_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 100)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('security_resets');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
