<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Search\CatalogSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\CatalogRequest;
use App\Models\Collection;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __invoke(Collection $collection, CatalogRequest $request, CatalogSearch $search, CatalogController $catalog): View
    {
        abort_unless($collection->status === 'active' && $collection->visibility === 'public', 404);

        return $catalog->render($request, $search, [...$request->validated(), 'collection' => $collection->id], $collection->name, $collection->description ?: 'Publikasi dalam rak '.$collection->name.'.');
    }
}
