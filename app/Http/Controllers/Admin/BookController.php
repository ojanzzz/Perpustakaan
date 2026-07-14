<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Documents\PdfIngestionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        $query = Book::query()->with(['publisher', 'categories'])->latest();
        if ($search = request('q')) {
            $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
        }
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return view('admin.books.index', ['books' => $query->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.books.create', [
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
            'collections' => Collection::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBookRequest $request, PdfIngestionService $service): RedirectResponse
    {
        try {
            $service->createDraft($request->safe()->except('pdf'), $request->file('pdf'), $request->user());
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['pdf' => $exception->getMessage()]);
        }

        return redirect('/admin/books')->with('status', 'Draft buku berhasil dibuat dan PDF masuk antrean pemrosesan.');
    }

    public function edit(Book $book): View
    {
        $book->load('categories', 'collections', 'reviews.user:id,name');

        return view('admin.books.edit', [
            'book' => $book,
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
            'collections' => Collection::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $data = $request->validated();
        $book->update([
            ...Arr::except($data, ['category_ids', 'collection_ids']),
            'download_enabled' => $request->boolean('download_enabled'),
            'print_enabled' => $request->boolean('print_enabled'),
            'updated_by' => $request->user()->id,
        ]);
        if ($request->has('category_ids')) {
            $book->categories()->sync($data['category_ids'] ?? []);
        }
        if ($request->has('collection_ids')) {
            $book->collections()->sync($data['collection_ids'] ?? []);
        }

        return redirect('/admin/books')->with('status', 'Metadata buku diperbarui.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return redirect('/admin/books')->with('status', 'Buku dipindahkan ke sampah.');
    }
}
