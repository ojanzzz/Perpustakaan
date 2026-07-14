<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CollectionRequest;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        return view('admin.collections.index', ['collections' => Collection::query()->withCount('books')->orderBy('sort_order')->orderBy('name')->paginate(20)]);
    }

    public function store(CollectionRequest $request): RedirectResponse
    {
        Collection::query()->create([...$request->validated(), 'slug' => $this->slug($request->string('name'))]);

        return redirect('/admin/collections')->with('status', 'Koleksi ditambahkan.');
    }

    public function update(CollectionRequest $request, Collection $collection): RedirectResponse
    {
        $collection->update([...$request->validated(), 'slug' => $this->slug($request->string('name'), $collection)]);

        return redirect('/admin/collections')->with('status', 'Koleksi diperbarui.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        if ($collection->books()->exists()) {
            throw ValidationException::withMessages(['collection' => 'Koleksi masih berisi buku.']);
        }
        $collection->delete();

        return redirect('/admin/collections')->with('status', 'Koleksi dihapus.');
    }

    private function slug(string $name, ?Collection $ignore = null): string
    {
        $base = Str::slug($name) ?: 'koleksi';
        $slug = $base;
        $i = 2;
        while (Collection::withTrashed()->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
