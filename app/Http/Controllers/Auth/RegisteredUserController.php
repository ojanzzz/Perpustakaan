<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $this->ensureRegistrationEnabled();

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureRegistrationEnabled();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
        ]);

        $user = User::query()->create([
            ...$validated,
            'role' => UserRole::Member,
            'admin_level' => null,
            'status' => AccountStatus::Active,
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }

    private function ensureRegistrationEnabled(): void
    {
        $enabled = DB::table('settings')
            ->where('key', 'member_registration_enabled')
            ->value('value');

        abort_unless(filter_var($enabled, FILTER_VALIDATE_BOOL), 404);
    }
}
