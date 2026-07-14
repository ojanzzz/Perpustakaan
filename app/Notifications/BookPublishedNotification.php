<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $bookId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $book = Book::query()->findOrFail($this->bookId);

        return [
            'title' => 'Buku baru: '.$book->title,
            'message' => 'Publikasi baru tersedia pada kategori yang Anda langgani.',
            'url' => route('books.show', $book, false),
            'book_id' => $book->id,
        ];
    }
}
