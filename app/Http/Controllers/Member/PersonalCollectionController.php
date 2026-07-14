<?php

namespace App\Http\Controllers\Member;

use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StorePersonalCollectionRequest;
use App\Models\Book;
use App\Models\PersonalCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PersonalCollectionController extends Controller
{
    public function index(): View
    {
        $collections = request()->user()->personalCollections()->with(['books' => fn ($query) => $query->limit(6)])->latest()->get();
        $favoriteBooks = request()->user()->favoriteBooks()->select('books.id', 'title', 'slug')->orderBy('title')->get();

        return view('member.collections', compact('collections', 'favoriteBooks'));
    }

    public function store(StorePersonalCollectionRequest $request): RedirectResponse
    {
        $base = Str::slug($request->validated('name')) ?: 'koleksi';
        $slug = $base;
        $counter = 2;
        while ($request->user()->personalCollections()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }
        $request->user()->personalCollections()->create([...$request->validated(), 'slug' => $slug]);

        return redirect()->route('member.collections')->with('status', 'Koleksi pribadi dibuat.');
    }

    public function addBook(PersonalCollection $personalCollection, Request $request, BookAccessService $access): RedirectResponse
    {
        abort_unless($personalCollection->user_id === $request->user()->id, 404);
        $data = $request->validate(['book_id' => ['required', 'integer', 'exists:books,id']]);
        $book = Book::findOrFail($data['book_id']);
        abort_unless($access->canView($book, $request->user()), 404);
        $personalCollection->books()->syncWithoutDetaching([$book->id]);

        return redirect()->route('member.collections')->with('status', 'Buku ditambahkan ke koleksi.');
    }

    public function removeBook(PersonalCollection $personalCollection, Book $book, Request $request): RedirectResponse
    {
        abort_unless($personalCollection->user_id === $request->user()->id, 404);
        $personalCollection->books()->detach($book);

        return back()->with('status', 'Buku dihapus dari koleksi.');
    }

    public function destroy(PersonalCollection $personalCollection, Request $request): RedirectResponse
    {
        abort_unless($personalCollection->user_id === $request->user()->id, 404);
        $personalCollection->delete();

        return back()->with('status', 'Koleksi pribadi dihapus.');
    }
}
