<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(BookAccessService $access): View
    {
        $user = request()->user();
        $base = $access->discoverableQuery($user);
        $latest = (clone $base)
            ->with(['authors:id,name', 'publisher:id,name'])
            ->latest('published_at')
            ->paginate(8)
            ->withQueryString();

        return view('public.home', [
            'latestBooks' => $latest,
        ]);
    }
}
