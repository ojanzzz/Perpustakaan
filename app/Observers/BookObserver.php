<?php

namespace App\Observers;

use App\Domain\Audit\AuditRecorder;
use App\Enums\BookStatus;
use App\Models\Book;
use App\Notifications\BookPublishedNotification;
use Illuminate\Support\Facades\Notification;

class BookObserver
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function created(Book $book): void
    {
        $this->audit->record('books.create', $book, after: $book->getAttributes());
    }

    public function updated(Book $book): void
    {
        $action = match (true) {
            $book->wasChanged('status') && $book->status === BookStatus::Published => 'books.publish',
            $book->wasChanged('status') && $book->status === BookStatus::Archived => 'books.archive',
            $book->wasChanged(['original_file', 'optimized_file']) => 'books.replace_file',
            default => 'books.update',
        };
        $this->audit->record($action, $book, $book->getOriginal(), $book->getChanges());

        if (! $book->wasChanged(['status', 'published_at'])
            || $book->status !== BookStatus::Published
            || ! $book->published_at?->isPast()) {
            return;
        }

        $subscribers = $book->categories()->with('subscribers')->get()
            ->flatMap->subscribers->unique('id')->values();
        if ($subscribers->isNotEmpty()) {
            Notification::send($subscribers, new BookPublishedNotification($book->id));
        }
    }

    public function deleted(Book $book): void
    {
        $this->audit->record('books.delete', $book, $book->getOriginal());
    }

    public function restored(Book $book): void
    {
        $this->audit->record('books.restore', $book, after: $book->getAttributes());
    }
}
