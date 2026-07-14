<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Catalog\BookAccessService;
use App\Enums\BookVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\UnlockBookRequest;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookController extends Controller
{
    public function show(Book $book, BookAccessService $access): View
    {
        $unlocked = (bool) session($access->passwordSessionKey($book), false);
        $locked = $access->isPasswordLocked($book, $unlocked);
        abort_unless($locked || $access->canView($book, request()->user(), $unlocked), 404);

        $book->load(['authors:id,name,slug', 'publisher:id,name,slug', 'language:id,name,code', 'categories:id,name,slug', 'collections:id,name,slug', 'tags:id,name,slug']);
        $related = $access->discoverableQuery(request()->user())
            ->whereKeyNot($book->id)
            ->when($book->categories->isNotEmpty(), fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->whereIn('categories.id', $book->categories->modelKeys())))
            ->with(['authors:id,name', 'publisher:id,name'])->limit(4)->get();

        return view('public.books.show', compact('book', 'locked', 'related'));
    }

    public function unlock(Book $book, UnlockBookRequest $request, BookAccessService $access): RedirectResponse
    {
        abort_unless($access->isPasswordLocked($book, false) && $book->visibility === BookVisibility::Password, 404);

        if (! $book->password_hash || ! Hash::check($request->validated('password'), $book->password_hash)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi dokumen tidak sesuai.']);
        }

        $request->session()->put($access->passwordSessionKey($book), true);

        return redirect()->route('books.show', $book);
    }
}
