<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — E-Perpustakaan Digital KPU</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside class="bg-slate-950 px-5 py-6 text-white lg:min-h-screen">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-white/10 pb-5 text-lg font-bold tracking-tight"><span class="grid h-10 w-14 shrink-0 place-items-center rounded-lg border border-white/15 bg-white/10 p-1.5"><img src="{{ asset('images/logo.png') }}" alt="Logo E-Perpustakaan Digital KPU" class="h-full w-full object-contain" width="813" height="433"></span><span>E-Perpustakaan KPU</span></a>
            <nav aria-label="Navigasi administrator" class="mt-6 grid grid-cols-2 gap-2 text-sm lg:grid-cols-1">
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Ringkasan</a>
                @can('books.view')<a href="{{ route('admin.books.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Buku</a>@endcan
                @can('books.view')<a href="{{ route('admin.collections.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Koleksi</a>@endcan
                @can('books.view')<a href="{{ route('admin.categories.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Kategori</a>@endcan
                @can('analytics.view')<a href="{{ route('admin.statistics.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Statistik</a>@endcan
                @can('feedback.manage')<a href="{{ route('admin.feedback.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Saran & laporan</a>@endcan
                @can('audit.view')<a href="{{ route('admin.audit.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Audit log</a>@endcan
                @can('backup.view')<a href="{{ route('admin.backups.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Backup</a>@endcan
                @can('users.manage_members')<a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pengguna</a>@endcan
                @can('settings.manage')<a href="{{ route('admin.settings.edit') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Pengaturan</a>@endcan
                <a href="{{ route('two-factor.setup') }}" class="rounded-lg px-3 py-2.5 hover:bg-white/10">Keamanan akun</a>
            </nav>
        </aside>
        <div>
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 lg:px-8">
                <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-red-700">Administrator</p><p class="font-semibold">{{ auth()->user()->name }}</p></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Keluar</button></form>
            </header>
            <main class="p-5 lg:p-8">
                @if(session('status'))<div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
                @if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Periksa kembali formulir:</strong><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
