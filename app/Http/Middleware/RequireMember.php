<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMember
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->role === UserRole::Member
            && $request->user()?->status === AccountStatus::Active,
            403,
        );

        return $next($request);
    }
}
