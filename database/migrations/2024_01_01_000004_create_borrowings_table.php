<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->date('borrow_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            // requested  = anggota mengajukan, menunggu persetujuan pustakawan
            // borrowed   = disetujui / dicatat langsung, sedang dipinjam
            // returned   = sudah dikembalikan & dikonfirmasi
            // lost       = dinyatakan hilang
            // rejected   = permintaan pinjam ditolak
            // cancelled  = permintaan dibatalkan sendiri oleh anggota
            $table->string('status', 20)->default('borrowed');
            $table->unsignedTinyInteger('extend_count')->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->boolean('fine_paid')->default(false);
            $table->timestamp('fine_paid_at')->nullable();
            $table->string('request_note')->nullable();   // catatan anggota saat mengajukan pinjam
            $table->string('decision_note')->nullable();   // catatan pustakawan saat menyetujui/menolak
            $table->timestamp('return_requested_at')->nullable(); // anggota mengajukan kembali, menunggu konfirmasi
            $table->string('notes')->nullable();
            $table->date('last_reminded')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
        });

        // Pertahanan berlapis di level database (selain validasi di BorrowingService):
        // status tidak akan pernah bisa diisi nilai di luar daftar yang sah, bahkan lewat
        // query manual/tool pihak ketiga.
        DB::statement("ALTER TABLE borrowings ADD CONSTRAINT borrowings_status_check CHECK (status IN ('requested','borrowed','returned','lost','rejected','cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
