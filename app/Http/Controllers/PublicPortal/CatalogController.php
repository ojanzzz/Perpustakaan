<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Search\CatalogSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\CatalogRequest;
use App\Models\Author;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Language;
use App\Models\Publisher;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(CatalogRequest $request, CatalogSearch $search): View
    {
        return $this->render($request, $search, $request->validated(), 'Katalog Buku', 'Temukan publikasi kepemiluan berdasarkan metadata yang Anda perlukan.');
    }

    public function latest(CatalogRequest $request, CatalogSearch $search): View
    {
        return $this->render($request, $search, [...$request->validated(), 'sort' => 'newest'], 'Koleksi Terbaru', 'Publikasi yang paling baru tersedia di perpustakaan digital.');
    }

    public function popular(CatalogRequest $request, CatalogSearch $search): View
    {
        return $this->render($request, $search, [...$request->validated(), 'sort' => 'popular'], 'Koleksi Terpopuler', 'Publikasi yang paling sering dibuka oleh pembaca.');
    }

    /** @param array<string, mixed> $filters */
    public function render(CatalogRequest $request, CatalogSearch $search, array $filters, string $heading, string $description): View
    {
        return view('public.catalog', [
            'books' => $search->paginate($filters, $request->user()),
            'filters' => $filters,
            'heading' => $heading,
            'description' => $description,
            'categories' => Category::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'collections' => Collection::where('status', 'active')->where('visibility', 'public')->orderBy('name')->get(['id', 'name']),
            'authors' => Author::orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::orderBy('name')->get(['id', 'name']),
            'languages' => Language::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'publicationTypes' => $search->publicationTypes($request->user()),
            'mode' => $filters['mode'] ?? 'grid',
        ]);
    }
}
