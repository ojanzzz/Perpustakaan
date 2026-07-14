<?php

namespace App\Http\Controllers\Reader;

use App\Domain\Analytics\AnalyticsRecorder;
use App\Domain\Catalog\BookAccessService;
use App\Domain\Documents\DocumentDeliveryService;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function show(Book $book, Request $request, BookAccessService $access, DocumentDeliveryService $documents): StreamedResponse
    {
        $this->authorizeDocument($book, $access);

        return $documents->stream($book, $request);
    }

    public function download(Book $book, Request $request, BookAccessService $access, DocumentDeliveryService $documents, AnalyticsRecorder $analytics): StreamedResponse
    {
        $this->authorizeDocument($book, $access);
        abort_unless($book->download_enabled, 403);
        abort_unless($documents->exists($book), 404);
        $analytics->recordDownload($book, $request);

        return $documents->stream($book, $request, true);
    }

    private function authorizeDocument(Book $book, BookAccessService $access): void
    {
        $unlocked = (bool) session($access->passwordSessionKey($book), false);
        abort_unless($access->canView($book, request()->user(), $unlocked), 404);
    }
}
