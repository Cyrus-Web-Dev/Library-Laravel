<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\BorrowingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class BorrowingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private BorrowingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BorrowingService::class);
    }

    #[Test]
    public function member_can_request_a_borrow_and_stock_is_untouched_until_approved(): void
    {
        $member = User::factory()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2]);

        $borrowing = $this->service->requestBorrow($member, $book, 'Mau baca minggu ini');

        $this->assertSame(Borrowing::REQUESTED, $borrowing->status);
        $this->assertSame(2, $book->fresh()->available_stock);
    }

    #[Test]
    public function duplicate_request_for_the_same_book_is_rejected(): void
    {
        $member = User::factory()->create();
        $book = Book::factory()->create();
        $this->service->requestBorrow($member, $book, null);

        $this->expectException(RuntimeException::class);
        $this->service->requestBorrow($member, $book, null);
    }

    #[Test]
    public function approve_reduces_stock_and_sets_due_date(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2]);
        $borrowing = $this->service->requestBorrow($member, $book, null);

        $this->service->approve($borrowing, $staff, 5, 'Silakan ambil di rak A');

        $borrowing->refresh();
        $this->assertSame(Borrowing::BORROWED, $borrowing->status);
        $this->assertSame(1, $book->fresh()->available_stock);
        $this->assertSame(now()->addDays(5)->toDateString(), $borrowing->due_date->toDateString());
    }

    #[Test]
    public function approve_fails_when_stock_is_already_zero(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 1]);
        $borrowing = $this->service->requestBorrow($member, $book, null);
        $book->update(['available_stock' => 0]); // habis karena transaksi lain, mis. dipinjamkan langsung

        $this->expectException(RuntimeException::class);
        $this->service->approve($borrowing, $staff, 7, null);
    }

    #[Test]
    public function reject_notifies_member_and_leaves_stock_untouched(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create(['available_stock' => 3]);
        $borrowing = $this->service->requestBorrow($member, $book, null);

        $this->service->reject($borrowing, $staff, 'Stok dipesan orang lain');

        $this->assertSame(Borrowing::REJECTED, $borrowing->fresh()->status);
        $this->assertSame(3, $book->fresh()->available_stock);
        $this->assertDatabaseHas('notifications', ['user_id' => $member->id, 'type' => 'warning']);
    }

    #[Test]
    public function member_cannot_exceed_max_books_including_pending_requests(): void
    {
        \App\Services\LibrarySettings::set('max_books_per_user', '1');
        $member = User::factory()->create();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $this->service->requestBorrow($member, $book1, null);

        $this->expectException(RuntimeException::class);
        $this->service->requestBorrow($member, $book2, null);
    }

    #[Test]
    public function full_lifecycle_request_approve_request_return_confirm_with_fine(): void
    {
        \App\Services\LibrarySettings::set('fine_per_day', '1000');
        \App\Services\LibrarySettings::set('fine_max', '20000');
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 1]);

        $borrowing = $this->service->requestBorrow($member, $book, null);
        $this->service->approve($borrowing, $staff, 7, null);

        // Mundurkan jatuh tempo supaya terlambat 5 hari.
        $borrowing->refresh()->forceFill(['due_date' => now()->subDays(5)])->saveQuietly();

        $this->service->requestReturn($borrowing->fresh());
        $this->assertNotNull($borrowing->fresh()->return_requested_at);

        $this->service->confirmReturn($borrowing->fresh(), $staff);

        $borrowing->refresh();
        $this->assertSame(Borrowing::RETURNED, $borrowing->status);
        $this->assertEquals(5000.00, $borrowing->fine_amount);
        $this->assertFalse($borrowing->fine_paid);
        $this->assertSame(1, $book->fresh()->available_stock);
    }

    #[Test]
    public function fine_is_capped_at_fine_max(): void
    {
        \App\Services\LibrarySettings::set('fine_per_day', '1000');
        \App\Services\LibrarySettings::set('fine_max', '20000');
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create();
        $borrowing = $this->service->requestBorrow($member, $book, null);
        $this->service->approve($borrowing, $staff, 7, null);
        $borrowing->refresh()->forceFill(['due_date' => now()->subDays(60)])->saveQuietly();

        $this->service->confirmReturn($borrowing->fresh(), $staff);

        $this->assertEquals(20000.00, $borrowing->fresh()->fine_amount);
    }

    #[Test]
    public function extend_adds_days_and_respects_the_max_extend_limit(): void
    {
        \App\Services\LibrarySettings::set('max_extend', '1');
        \App\Services\LibrarySettings::set('extend_days', '7');
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create();
        $borrowing = $this->service->requestBorrow($member, $book, null);
        $this->service->approve($borrowing, $staff, 7, null);
        $originalDue = $borrowing->fresh()->due_date;

        $this->service->extend($borrowing->fresh());
        $borrowing->refresh();
        $this->assertSame($originalDue->copy()->addDays(7)->toDateString(), $borrowing->due_date->toDateString());
        $this->assertSame(1, $borrowing->extend_count);

        $this->expectException(RuntimeException::class);
        $this->service->extend($borrowing->fresh());
    }

    #[Test]
    public function overdue_loan_cannot_be_extended(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create();
        $borrowing = $this->service->requestBorrow($member, $book, null);
        $this->service->approve($borrowing, $staff, 7, null);
        $borrowing->refresh()->forceFill(['due_date' => now()->subDay()])->saveQuietly();

        $this->expectException(RuntimeException::class);
        $this->service->extend($borrowing->fresh());
    }

    #[Test]
    public function lost_book_charges_the_book_price_and_decrements_total_stock(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2, 'price' => 75000]);
        $borrowing = $this->service->requestBorrow($member, $book, null);
        $this->service->approve($borrowing, $staff, 7, null);

        $this->service->markLost($borrowing->fresh(), $staff);

        $borrowing->refresh();
        $book->refresh();
        $this->assertSame(Borrowing::LOST, $borrowing->status);
        $this->assertEquals(75000.00, $borrowing->fine_amount);
        $this->assertSame(1, $book->total_stock);
        $this->assertSame(1, $book->available_stock);
    }

    #[Test]
    public function member_blocked_from_new_requests_while_unpaid_fine_exists(): void
    {
        \App\Services\LibrarySettings::set('block_if_fine', '1');
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();
        $borrowingA = $this->service->requestBorrow($member, $bookA, null);
        $this->service->approve($borrowingA, $staff, 7, null);
        $borrowingA->refresh()->forceFill(['due_date' => now()->subDays(3)])->saveQuietly();
        $this->service->confirmReturn($borrowingA->fresh(), $staff); // denda tercatat, belum lunas

        $this->expectException(RuntimeException::class);
        $this->service->requestBorrow($member, $bookB, null);
    }

    #[Test]
    public function paying_the_fine_unblocks_new_requests(): void
    {
        \App\Services\LibrarySettings::set('block_if_fine', '1');
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();
        $borrowingA = $this->service->requestBorrow($member, $bookA, null);
        $this->service->approve($borrowingA, $staff, 7, null);
        $borrowingA->refresh()->forceFill(['due_date' => now()->subDays(3)])->saveQuietly();
        $this->service->confirmReturn($borrowingA->fresh(), $staff);

        $this->service->payFine($borrowingA->fresh(), $staff);
        $borrowingB = $this->service->requestBorrow($member, $bookB, null);

        $this->assertSame(Borrowing::REQUESTED, $borrowingB->status);
    }

    #[Test]
    public function member_can_cancel_a_pending_request_but_not_after_it_is_decided(): void
    {
        $member = User::factory()->create();
        $staff = User::factory()->pustakawan()->create();
        $book = Book::factory()->create();
        $borrowing = $this->service->requestBorrow($member, $book, null);

        $this->service->cancelRequest($borrowing->fresh());
        $this->assertSame(Borrowing::CANCELLED, $borrowing->fresh()->status);

        $borrowing2 = $this->service->requestBorrow($member, $book, null);
        $this->service->approve($borrowing2, $staff, 7, null);

        $this->expectException(RuntimeException::class);
        $this->service->cancelRequest($borrowing2->fresh());
    }
}
