<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\BookAccessService;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Book;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request, BookAccessService $access): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['book_id'])) {
            $book = Book::findOrFail($data['book_id']);
            abort_unless($access->canView($book, $request->user()), 404);
        }
        Feedback::query()->create([...$data, 'user_id' => $request->user()?->id, 'status' => 'new']);

        return back()->with('status', 'Pesan Anda telah diterima. Terima kasih.');
    }
}
