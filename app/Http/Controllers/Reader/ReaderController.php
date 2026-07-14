<?php

namespace App\Http\Controllers\Reader;

use App\Domain\Catalog\BookAccessService;
use App\Domain\Documents\DocumentDeliveryService;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function __invoke(Book $book, BookAccessService $access, DocumentDeliveryService $documents): View
    {
        $unlocked = (bool) session($access->passwordSessionKey($book), false);
        abort_unless($access->canView($book, request()->user(), $unlocked) && $documents->exists($book), 404);

        $initialPage = max(1, min((int) request()->integer('page', 1), max(1, (int) $book->page_count)));
        $documentUrl = URL::temporarySignedRoute('reader.document', now()->addMinutes(10), $book);
        $downloadUrl = $book->download_enabled
            ? URL::temporarySignedRoute('reader.download', now()->addMinutes(10), $book)
            : null;
        $user = request()->user();
        $progress = $user?->readingHistories()->where('book_id', $book->id)->first();
        $bookmarks = $user?->bookmarks()->where('book_id', $book->id)->orderBy('page')->get() ?? collect();
        $favorited = $user?->favorites()->where('book_id', $book->id)->exists() ?? false;

        return view('reader.show', compact(
            'book', 'initialPage', 'documentUrl', 'downloadUrl', 'progress', 'bookmarks', 'favorited'
        ));
    }
}
