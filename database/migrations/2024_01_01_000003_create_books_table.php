<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('book_code', 20)->nullable()->unique(); // BK-0001, diisi setelah insert
            $table->string('isbn', 20)->nullable();
            $table->string('title');
            $table->string('author', 150);
            $table->string('publisher', 100)->nullable();
            $table->smallInteger('publication_year')->nullable();
            $table->foreignId('genre_id')->nullable()->constrained('genres')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('book_types')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('ebook_file')->nullable(); // PDF privat, disimpan di storage/app (bukan public)
            $table->string('location', 50)->nullable();
            $table->unsignedInteger('total_stock')->default(1);
            $table->unsignedInteger('available_stock')->default(1);
            $table->unsignedInteger('pages')->nullable();
            $table->string('language', 30)->default('Indonesia');
            $table->decimal('price', 12, 2)->nullable(); // dipakai sebagai denda jika buku hilang
            $table->string('status', 20)->default('available'); // available | unavailable | archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('title');
            $table->index('author');
            $table->index('status');
        });

        DB::statement("ALTER TABLE books ADD CONSTRAINT books_status_check CHECK (status IN ('available','unavailable','archived'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
