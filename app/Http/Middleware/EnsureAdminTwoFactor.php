<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->role !== UserRole::Admin) {
            return $next($request);
        }
        if (! $user->two_factor_enabled_at) {
            if (Setting::valueOf('admin_2fa_required', false)) {
                return redirect()->route('two-factor.setup');
            }

            return $next($request);
        }
        if (! $request->session()->has('two_factor.confirmed_at')) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
