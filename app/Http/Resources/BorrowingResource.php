<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Borrowing */
class BorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $state = $this->state();

        return [
            'id' => $this->id,
            'code' => $this->code ?? null,
            'book' => $this->whenLoaded('book', fn () => [
                'id' => $this->book->id,
                'title' => $this->book->title,
                'author' => $this->book->author,
                'cover_url' => $this->book->cover_url,
                'book_code' => $this->book->book_code,
            ]),
            'user' => $this->when(
                $this->relationLoaded('user') && $request->user()?->isStaff(),
                fn () => [
                    'id' => $this->user->id,
                    'full_name' => $this->user->full_name,
                    'member_number' => method_exists($this->user, 'memberNumber') ? $this->user->memberNumber() : null,
                    'member_code' => $this->user->member_code,
                    'username' => $this->user->username,
                ]
            ),
            'borrow_date' => $this->borrow_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'return_date' => $this->return_date?->toDateString(),
            'return_requested_at' => $this->return_requested_at?->toDateTimeString(),
            'status' => $this->status,
            'status_label' => $state['label'],
            'status_tone' => $state['tone'],
            'late_days' => $state['lateDays'],
            'fine_amount' => (float) $this->fine_amount,
            'fine_paid' => (bool) $this->fine_paid,
            'decision_note' => $this->decision_note,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
