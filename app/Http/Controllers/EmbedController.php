<?php

namespace App\Http\Controllers;

use App\Domain\Catalog\BookAccessService;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\View\View;

class EmbedController extends Controller
{
    public function book(Book $book, BookAccessService $access): View
    {
        abort_unless($access->canView($book, request()->user()), 404);
        $book->load(['authors:id,name', 'publisher:id,name']);

        return view('embed.index', ['title' => $book->title, 'books' => collect([$book])]);
    }

    public function collection(Collection $collection, BookAccessService $access): View
    {
        abort_unless($collection->status === 'active', 404);
        $books = $access->discoverableQuery(request()->user())->whereHas('collections', fn ($query) => $query->whereKey($collection->id))->with(['authors:id,name', 'publisher:id,name'])->limit(24)->get();

        return view('embed.index', ['title' => $collection->name, 'books' => $books]);
    }

    public function category(Category $category, BookAccessService $access): View
    {
        abort_unless($category->status === 'active', 404);
        $books = $access->discoverableQuery(request()->user())->whereHas('categories', fn ($query) => $query->whereKey($category->id))->with(['authors:id,name', 'publisher:id,name'])->limit(24)->get();

        return view('embed.index', ['title' => $category->name, 'books' => $books]);
    }
}
