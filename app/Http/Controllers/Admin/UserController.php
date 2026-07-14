<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditRecorder;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::query()->withTrashed()->latest()->paginate(25)]);
    }

    public function store(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users'],
            'password' => ['required', 'string', 'min:12'],
            'role' => ['required', Rule::in([UserRole::Member->value, UserRole::Superadmin->value])],
        ]);
        $user = User::query()->create([...$data, 'password' => Hash::make($data['password']), 'status' => AccountStatus::Active, 'email_verified_at' => now()]);
        $audit->record('users.create', $user, actor: $request->user(), after: ['role' => $user->role->value]);

        return redirect()->route('admin.users.index')->with('status', 'Akun dibuat.');
    }

    public function update(User $user, Request $request, AuditRecorder $audit): RedirectResponse
    {
        Gate::authorize('update', $user);
        abort_if($user->is($request->user()) && $request->input('status') !== AccountStatus::Active->value, 422, 'Akun sendiri tidak dapat dinonaktifkan.');
        $data = $request->validate(['status' => ['required', Rule::enum(AccountStatus::class)]]);
        $before = ['status' => $user->status->value, 'role' => $user->role->value];
        $user->update(['status' => $data['status']]);
        $audit->record('users.update', $user, $before, ['status' => $user->status->value, 'role' => $user->role->value], $request->user());

        return redirect()->route('admin.users.index')->with('status', 'Akun diperbarui.');
    }

    public function permissions(User $user): View
    {
        abort_unless($user->role === UserRole::Superadmin, 403);

        return view('admin.users.permissions', ['managedUser' => $user->load('permissions'), 'permissions' => Permission::query()->orderBy('group')->orderBy('name')->get()]);
    }

    public function updatePermissions(User $user, Request $request, AuditRecorder $audit): RedirectResponse
    {
        abort_unless($user->role === UserRole::Superadmin, 403);

        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => [Rule::in(['allow', 'deny', 'inherit'])]]);
        $sync = collect($data['permissions'] ?? [])->reject(fn ($value) => $value === 'inherit')->mapWithKeys(fn ($value, $id) => [(int) $id => ['allowed' => $value === 'allow']])->all();
        $user->permissions()->sync($sync);
        $audit->record('permissions.update', $user, actor: $request->user(), after: ['overrides' => count($sync)]);

        return back()->with('status', 'Override permission diperbarui.');
    }
}
