<?php

namespace App\Providers;

use App\Domain\Audit\AuditRecorder;
use App\Domain\Authorization\PermissionService;
use App\Models\Book;
use App\Models\User;
use App\Observers\BookObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Book::observe(BookObserver::class);
        User::observe(UserObserver::class);
        Event::listen(Login::class, fn (Login $event) => app(AuditRecorder::class)->record('auth.login', $event->user, actor: $event->user));
        Event::listen(Logout::class, fn (Logout $event) => app(AuditRecorder::class)->record('auth.logout', $event->user, actor: $event->user));
        Event::listen(Failed::class, fn (Failed $event) => app(AuditRecorder::class)->record('auth.failed', $event->user instanceof User ? $event->user : null, actor: $event->user instanceof User ? $event->user : null));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        Gate::before(function (User $user, string $ability): ?bool {
            if (! DB::table('permissions')->where('name', $ability)->exists()) {
                return null;
            }

            return app(PermissionService::class)->allows($user, $ability);
        });
    }
}
