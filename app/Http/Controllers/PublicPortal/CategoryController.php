<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Search\CatalogSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\CatalogRequest;
use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __invoke(Category $category, CatalogRequest $request, CatalogSearch $search, CatalogController $catalog): View
    {
        abort_unless($category->status === 'active', 404);

        return $catalog->render($request, $search, [...$request->validated(), 'category' => $category->id], $category->name, $category->description ?: 'Publikasi dalam kategori '.$category->name.'.');
    }
}
