<?php

namespace App\Http\Controllers\Member;

use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function favorites(BookAccessService $access): View
    {
        $books = $access->discoverableQuery(request()->user())
            ->whereHas('favorites', fn ($query) => $query->where('user_id', request()->user()->id))
            ->with(['authors:id,name', 'publisher:id,name'])->latest('books.created_at')->paginate(12);

        return view('member.favorites', compact('books'));
    }

    public function history(BookAccessService $access): View
    {
        $histories = request()->user()->readingHistories()
            ->whereHas('book', fn ($query) => $query->whereIn('id', $access->discoverableQuery(request()->user())->select('id')))
            ->with(['book.authors:id,name'])->latest('last_read_at')->paginate(15);

        return view('member.history', compact('histories'));
    }

    public function bookmarks(BookAccessService $access): View
    {
        $bookmarks = request()->user()->bookmarks()
            ->whereHas('book', fn ($query) => $query->whereIn('id', $access->discoverableQuery(request()->user())->select('id')))
            ->with('book:id,title,slug,cover_image,page_count')->latest()->paginate(20);

        return view('member.bookmarks', compact('bookmarks'));
    }
}
