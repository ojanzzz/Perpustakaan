<?php

namespace App\Domain\Catalog;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Notifications\BookPublishedNotification;
use Illuminate\Support\Facades\Notification;

class BookPublicationNotifier
{
    public function notify(Book $book): void
    {
        if ($book->status !== BookStatus::Published || ! $book->published_at?->isPast()) {
            return;
        }

        $subscribers = $book->categories()->with('subscribers')->get()
            ->flatMap->subscribers->unique('id')->values();

        if ($subscribers->isNotEmpty()) {
            Notification::send($subscribers, new BookPublishedNotification($book->id));
        }
    }
}
