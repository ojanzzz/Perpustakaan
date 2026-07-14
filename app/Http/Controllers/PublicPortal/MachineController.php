<?php

namespace App\Http\Controllers\PublicPortal;

use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Http\Response;

class MachineController extends Controller
{
    public function sitemap(BookAccessService $access): Response
    {
        return response()->view('public.sitemap', [
            'books' => $access->discoverableQuery(null)->get(['slug', 'updated_at']),
            'categories' => Category::where('status', 'active')->get(['slug', 'updated_at']),
            'collections' => Collection::where('status', 'active')->where('visibility', 'public')->get(['slug', 'updated_at']),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
