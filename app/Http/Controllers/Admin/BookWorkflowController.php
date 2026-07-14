<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\PublicationWorkflow;
use App\Enums\BookStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookWorkflowController extends Controller
{
    public function submit(Book $book, Request $request, PublicationWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);
        $workflow->transition($book, $request->user(), 'submitted', $data['notes'] ?? null);

        return back()->with('status', 'Buku dikirim untuk ditinjau.');
    }

    public function return(Book $book, Request $request, PublicationWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'max:2000']]);
        $workflow->transition($book, $request->user(), 'returned', $data['notes']);

        return back()->with('status', 'Buku dikembalikan kepada editor.');
    }

    public function publish(Book $book, Request $request, PublicationWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000'], 'published_at' => ['nullable', 'date']]);
        $workflow->transition($book, $request->user(), 'published', $data['notes'] ?? null, isset($data['published_at']) ? new \DateTimeImmutable($data['published_at']) : null);

        return back()->with('status', $book->fresh()->status === BookStatus::Scheduled ? 'Publikasi dijadwalkan.' : 'Buku diterbitkan.');
    }

    public function archive(Book $book, Request $request, PublicationWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);
        $workflow->transition($book, $request->user(), 'archived', $data['notes'] ?? null);

        return back()->with('status', 'Buku diarsipkan.');
    }
}
