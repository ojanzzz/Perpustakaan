<?php

namespace App\Domain\Catalog;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\BookReview;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PublicationWorkflow
{
    public function transition(Book $book, User $actor, string $action, ?string $notes = null, ?\DateTimeInterface $publishAt = null): Book
    {
        [$allowedFrom, $to] = match ($action) {
            'submitted' => [[BookStatus::Draft], BookStatus::PendingReview],
            'returned' => [[BookStatus::PendingReview], BookStatus::Draft],
            'published' => [[BookStatus::PendingReview, BookStatus::Scheduled], $publishAt && $publishAt > now() ? BookStatus::Scheduled : BookStatus::Published],
            'archived' => [[BookStatus::Published, BookStatus::Scheduled, BookStatus::Private], BookStatus::Archived],
            default => throw ValidationException::withMessages(['action' => 'Aksi workflow tidak valid.']),
        };
        if (! in_array($book->status, $allowedFrom, true)) {
            throw ValidationException::withMessages(['action' => 'Status buku tidak mengizinkan aksi ini.']);
        }
        if ($action === 'returned' && blank($notes)) {
            throw ValidationException::withMessages(['notes' => 'Catatan perbaikan wajib diisi.']);
        }
        $from = $book->status;
        $book->update([
            'status' => $to,
            'published_at' => $action === 'published' ? ($publishAt ?? now()) : $book->published_at,
            'updated_by' => $actor->id,
        ]);
        BookReview::query()->create(['book_id' => $book->id, 'user_id' => $actor->id, 'action' => $action, 'notes' => $notes, 'from_status' => $from->value, 'to_status' => $to->value, 'created_at' => now()]);

        return $book->refresh();
    }
}
