<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', ['settings' => [
            'site_name' => Setting::valueOf('site_name', config('app.name')),
            'member_registration_enabled' => Setting::valueOf('member_registration_enabled', false),
            'admin_2fa_required' => Setting::valueOf('admin_2fa_required', false),
            'embed_allowed_domains' => Setting::valueOf('embed_allowed_domains', ''),
        ]]);
    }

    public function update(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'member_registration_enabled' => ['nullable', 'boolean'],
            'admin_2fa_required' => ['nullable', 'boolean'],
            'embed_allowed_domains' => ['nullable', 'string', 'max:4000'],
        ]);
        $domains = preg_split('/[\s,]+/', strtolower($data['embed_allowed_domains'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($domains as $domain) {
            abort_unless((bool) filter_var('https://'.$domain, FILTER_VALIDATE_URL) && ! str_contains($domain, '/'), 422, 'Domain embed tidak valid.');
        }
        Setting::putValue('site_name', $data['site_name']);
        Setting::putValue('member_registration_enabled', $request->boolean('member_registration_enabled') ? 'true' : 'false', 'auth', 'boolean', true);
        Setting::putValue('admin_2fa_required', $request->boolean('admin_2fa_required') ? 'true' : 'false', 'security', 'boolean');
        Setting::putValue('embed_allowed_domains', implode("\n", array_unique($domains)), 'embed');
        $audit->record('settings.update', actor: $request->user(), after: ['keys' => ['site_name', 'member_registration_enabled', 'admin_2fa_required', 'embed_allowed_domains']]);

        return redirect()->route('admin.settings.edit')->with('status', 'Pengaturan sistem diperbarui.');
    }
}
