<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', ['categories' => Category::query()->with('parent')->withCount('books')->orderBy('sort_order')->orderBy('name')->paginate(20)]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create([...$request->validated(), 'slug' => $this->slug($request->string('name'))]);

        return redirect('/admin/categories')->with('status', 'Kategori ditambahkan.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        if ((int) $request->input('parent_id') === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'Kategori tidak dapat menjadi induk dirinya sendiri.']);
        }
        $category->update([...$request->validated(), 'slug' => $this->slug($request->string('name'), $category)]);

        return redirect('/admin/categories')->with('status', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists() || $category->books()->exists()) {
            throw ValidationException::withMessages(['category' => 'Kategori masih memiliki subkategori atau buku.']);
        }
        $category->delete();

        return redirect('/admin/categories')->with('status', 'Kategori dihapus.');
    }

    private function slug(string $name, ?Category $ignore = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $i = 2;
        while (Category::withTrashed()->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
