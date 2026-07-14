<x-layouts.admin title="Pengguna">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-red-700 dark:text-red-400">Akses sistem</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 dark:text-white">Pengguna</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                Kelola akun anggota dan superadmin. Akses public tidak memerlukan dan tidak dapat memiliki akun.
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <span class="text-slate-500 dark:text-slate-400">Total akun</span>
            <strong class="ml-2 text-lg text-slate-950 dark:text-white">{{ $users->total() }}</strong>
        </div>
    </div>

    @can('users.manage_admins')
        <details class="group mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-bold text-slate-950 marker:content-none dark:text-white">
                <span>Buat akun baru</span>
                <span class="grid size-8 place-items-center rounded-full bg-slate-100 text-xl transition group-open:rotate-45 dark:bg-slate-800" aria-hidden="true">+</span>
            </summary>
            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-4 border-t border-slate-200 p-5 sm:grid-cols-2 dark:border-slate-700">
                @csrf
                <label class="grid gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Nama
                    <input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="rounded-xl border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-950">
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" class="rounded-xl border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-950">
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Kata sandi
                    <input type="password" name="password" required minlength="12" autocomplete="new-password" class="rounded-xl border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-950">
                    <span class="font-normal text-slate-500 dark:text-slate-400">Minimal 12 karakter.</span>
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Peran akun
                    <select name="role" required class="rounded-xl border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-950">
                        <option value="member" @selected(old('role', 'member') === 'member')>Anggota</option>
                        <option value="superadmin" @selected(old('role') === 'superadmin')>Superadmin</option>
                    </select>
                </label>
                <div class="sm:col-span-2">
                    <button class="rounded-xl bg-red-700 px-5 py-3 font-bold text-white shadow-sm transition hover:bg-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                        Buat akun
                    </button>
                </div>
            </form>
        </details>
    @endcan

    <div class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/80 dark:text-slate-300">
                    <tr>
                        <th class="px-5 py-4">Pengguna</th>
                        <th class="px-5 py-4">Peran</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse($users as $user)
                        <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                            <td class="px-5 py-4">
                                <strong class="text-slate-950 dark:text-white">{{ $user->name }}</strong>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $user->role === \App\Enums\UserRole::Superadmin ? 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300' }}">
                                    {{ $user->role === \App\Enums\UserRole::Superadmin ? 'Superadmin' : 'Anggota' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="status-{{ $user->id }}">Status {{ $user->name }}</label>
                                    <select id="status-{{ $user->id }}" name="status" class="rounded-lg border-slate-300 bg-white text-xs dark:border-slate-600 dark:bg-slate-950">
                                        @foreach(\App\Enums\AccountStatus::cases() as $status)
                                            <option value="{{ $status->value }}" @selected($user->status === $status)>{{ ucfirst($status->value) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="font-bold text-red-700 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Simpan</button>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-right">
                                @can('permissions.manage')
                                    @if($user->role === \App\Enums\UserRole::Superadmin)
                                        <a class="font-bold text-red-700 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" href="{{ route('admin.users.permissions', $user) }}">Permission</a>
                                    @else
                                        <span class="text-xs text-slate-400 dark:text-slate-500">Tidak berlaku</span>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">Belum ada akun.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</x-layouts.admin>
