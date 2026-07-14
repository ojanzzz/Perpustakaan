<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditRecorder;
use App\Domain\Security\TotpService;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function setup(Request $request, TotpService $totp): View
    {
        $this->ensureSuperadmin($request);
        $secret = $request->user()->two_factor_secret ?: $request->session()->get('two_factor.pending_secret', $totp->generateSecret());
        $request->session()->put('two_factor.pending_secret', $secret);

        return view('auth.two-factor-setup', ['secret' => $secret, 'uri' => $totp->provisioningUri($secret, $request->user()->email)]);
    }

    public function enable(Request $request, TotpService $totp, AuditRecorder $audit): RedirectResponse
    {
        $this->ensureSuperadmin($request);
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $secret = (string) $request->session()->get('two_factor.pending_secret');
        if (! $secret || ! $totp->verify($secret, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Kode autentikator tidak valid.']);
        }
        $plainCodes = $totp->recoveryCodes();
        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $plainCodes),
            'two_factor_enabled_at' => now(),
        ])->save();
        $request->session()->forget('two_factor.pending_secret');
        $request->session()->put('two_factor.confirmed_at', time());
        $request->session()->flash('two_factor.recovery_codes', $plainCodes);
        $audit->record('security.2fa.enabled', $request->user());

        return redirect('/admin')->with('status', 'Autentikasi dua faktor telah aktif. Simpan kode pemulihan.');
    }

    public function challenge(Request $request): View
    {
        $this->ensureSuperadmin($request);

        return view('auth.two-factor-challenge');
    }

    public function confirm(Request $request, TotpService $totp, AuditRecorder $audit): RedirectResponse
    {
        $this->ensureSuperadmin($request);
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = $request->user();
        $valid = $user->two_factor_secret && $totp->verify($user->two_factor_secret, preg_replace('/\D/', '', $data['code']));
        if (! $valid) {
            $recovery = strtoupper(str_replace('-', '', $data['code']));
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check($recovery, $hash)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->saveQuietly();
                    $valid = true;
                    break;
                }
            }
        }
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Kode autentikasi tidak valid.']);
        }
        $request->session()->put('two_factor.confirmed_at', time());
        $audit->record('security.2fa.challenge_passed', $user);

        return redirect()->intended('/admin');
    }

    public function disable(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $this->ensureSuperadmin($request);
        $data = $request->validate(['password' => ['required', 'current_password']]);
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_enabled_at' => null])->save();
        $request->session()->forget('two_factor.confirmed_at');
        $audit->record('security.2fa.disabled', $request->user(), null, ['password_confirmed' => isset($data['password'])]);

        return back()->with('status', 'Autentikasi dua faktor dinonaktifkan.');
    }

    private function ensureSuperadmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::Superadmin, 403);
    }
}
