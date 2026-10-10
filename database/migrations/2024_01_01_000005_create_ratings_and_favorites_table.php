<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5, divalidasi di FormRequest
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(true); // false = disembunyikan pustakawan
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
        });

        DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_rating_check CHECK (rating BETWEEN 1 AND 5)');

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('ratings');
    }
};
