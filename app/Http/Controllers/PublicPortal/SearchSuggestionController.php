<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request, BookAccessService $access): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $like = '%'.addcslashes(trim($data['q']), '%_\\').'%';
        $books = $access->discoverableQuery($request->user())
            ->where('title', 'like', $like)
            ->with(['authors:id,name', 'publisher:id,name'])
            ->orderBy('title')->limit(6)->get()
            ->map(fn ($book) => [
                'title' => $book->title,
                'url' => route('books.show', $book),
                'byline' => $book->authors->pluck('name')->join(', ') ?: $book->publisher?->name,
            ]);

        return response()->json(['data' => $books]);
    }
}
