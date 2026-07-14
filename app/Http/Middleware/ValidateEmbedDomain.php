<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmbedDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $referer = $request->headers->get('referer');
        if (! $referer) {
            abort_if($request->headers->get('sec-fetch-dest') === 'iframe', 403, 'Referer embed wajib tersedia.');

            return $next($request);
        }
        $host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        $ownHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        $domains = preg_split('/[\s,]+/', strtolower((string) Setting::valueOf('embed_allowed_domains', '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $allowed = $host === $ownHost || collect($domains)->contains(fn (string $domain) => $host === $domain || str_ends_with($host, '.'.$domain));
        abort_unless($allowed, 403, 'Domain tidak diizinkan untuk embed.');

        return $next($request);
    }
}
