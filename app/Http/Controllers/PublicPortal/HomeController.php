<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(BookAccessService $access): View
    {
        $user = request()->user();
        $base = $access->discoverableQuery($user);
        $latest = (clone $base)->with(['authors:id,name', 'publisher:id,name'])->withCount('views')->latest('published_at')->limit(6)->get();
        $popular = (clone $base)->with(['authors:id,name', 'publisher:id,name'])->withCount('views')->orderByDesc('views_count')->latest('published_at')->limit(5)->get();
        $bookIds = (clone $base)->select('books.id');

        $categories = Category::query()->where('status', 'active')
            ->withCount(['books as accessible_books_count' => fn ($query) => $query->whereIn('books.id', clone $bookIds)])
            ->orderBy('sort_order')->limit(8)->get();
        $collections = Collection::query()->where('status', 'active')->where('visibility', 'public')
            ->withCount(['books as accessible_books_count' => fn ($query) => $query->whereIn('books.id', clone $bookIds)])
            ->orderBy('sort_order')->limit(5)->get();

        $announcement = DB::table('announcements')->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest()->first();

        return view('public.home', [
            'latestBooks' => $latest,
            'popularBooks' => $popular,
            'categories' => $categories,
            'collections' => $collections,
            'announcement' => $announcement,
            'statistics' => [
                'books' => (clone $base)->count(),
                'categories' => Category::where('status', 'active')->count(),
                'collections' => Collection::where('status', 'active')->where('visibility', 'public')->count(),
                'views' => DB::table('book_views')->count(),
            ],
        ]);
    }
}
