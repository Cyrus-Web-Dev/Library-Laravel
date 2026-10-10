<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('author', 150)->nullable();
            $table->string('genre', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('publisher', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|fulfilled
            $table->text('admin_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
        DB::statement("ALTER TABLE book_requests ADD CONSTRAINT book_requests_status_check CHECK (status IN ('pending','approved','rejected','fulfilled'))");

        Schema::create('staff_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->text('experience')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->text('admin_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
        DB::statement("ALTER TABLE staff_applications ADD CONSTRAINT staff_applications_status_check CHECK (status IN ('pending','approved','rejected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_applications');
        Schema::dropIfExists('book_requests');
    }
};
