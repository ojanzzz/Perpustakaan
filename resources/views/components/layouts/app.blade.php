@props([
    'title' => 'E-Perpustakaan Digital KPU',
    'description' => 'Portal katalog dan publikasi digital kepemiluan.',
    'canonical' => url()->current(),
    'image' => null,
    'jsonLd' => null,
])
<!DOCTYPE html>
<html lang="id" class="scroll-smooth" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#991b1b">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/logo.png" type="image/png">
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if($image)<meta property="og:image" content="{{ $image }}">@endif
    <title>{{ $title }}</title>
    @if($jsonLd)<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col antialiased">
    <a href="#konten-utama" class="skip-link">Lewati ke konten utama</a>
    <header class="site-header" data-site-header>
        <div class="portal-container flex h-[68px] items-center justify-between gap-5">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 text-navy" aria-label="E-Perpustakaan Digital KPU — Beranda">
                <span class="grid h-10 w-14 shrink-0 place-items-center rounded-lg bg-navy p-1.5 shadow-sm"><img src="{{ asset('images/logo.png') }}" alt="Logo E-Perpustakaan Digital KPU" class="h-full w-full object-contain" width="813" height="433"></span>
                <span class="min-w-0"><strong class="block truncate text-[15px] leading-tight sm:text-base">E-Perpustakaan Digital KPU</strong></span>
            </a>
            <nav aria-label="Navigasi utama" class="hidden items-center gap-1 lg:flex">
                @foreach([
                    ['Publikasi', route('home').'#publikasi', 'home'],
                    ['Panduan', route('guide'), 'guide'], ['Tentang', route('about'), 'about'], ['Kontak', route('contact'), 'contact']
                ] as [$label, $href, $routePattern])
                    <a href="{{ $href }}" @class(['nav-link', 'is-active' => request()->routeIs($routePattern)]) @if(request()->routeIs($routePattern)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <button type="button" class="icon-button hidden sm:grid" data-theme-toggle aria-label="Aktifkan mode gelap" title="Mode gelap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 15.2A8 8 0 0 1 8.8 4 8 8 0 1 0 20 15.2z"/></svg>
                </button>
                <button type="button" class="icon-button hidden sm:grid" data-contrast-toggle aria-label="Aktifkan kontras tinggi" title="Kontras tinggi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 0 1 0 16z"/></svg>
                </button>
                @auth
                    @if(auth()->user()->role === \App\Enums\UserRole::Superadmin)<a href="{{ route('admin.dashboard') }}" class="button-secondary hidden sm:inline-flex">Dashboard</a>
                    @else<a href="{{ route('member.profile') }}" class="button-secondary hidden sm:inline-flex">Akun saya</a>@endif
                    <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">@csrf<button class="button-primary">Keluar</button></form>
                @else
                    <a href="{{ route('login') }}" class="button-primary hidden sm:inline-flex">Masuk</a>
                @endauth
                <button type="button" class="icon-button lg:hidden" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="Buka navigasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
        <nav id="mobile-menu" aria-label="Navigasi seluler" class="mobile-menu" hidden>
            <div class="portal-container grid gap-1 py-4">
                <a href="{{ route('home') }}#publikasi">Publikasi</a><a href="{{ route('guide') }}">Panduan</a><a href="{{ route('about') }}">Tentang</a><a href="{{ route('contact') }}">Kontak</a>
                @guest<a href="{{ route('login') }}" class="mt-2 font-semibold text-red-700">Masuk</a>
                @else @if(auth()->user()->role === \App\Enums\UserRole::Member)<a href="{{ route('member.profile') }}">Akun saya</a><a href="{{ route('member.favorites') }}">Favorit</a><a href="{{ route('member.notifications') }}">Notifikasi</a>@endif @endguest
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-200 pt-4 sm:hidden">
                    <button type="button" class="button-secondary" data-theme-toggle>Mode gelap</button>
                    <button type="button" class="button-secondary" data-contrast-toggle>Kontras tinggi</button>
                </div>
            </div>
        </nav>
    </header>
    <main id="konten-utama" class="flex-1">{{ $slot }}</main>
    <footer class="site-footer site-footer-compact">
        <div class="portal-container flex flex-col gap-4 py-5 text-sm sm:flex-row sm:items-center sm:justify-between">
            <span>&copy; {{ date('Y') }} E-Perpustakaan Digital KPU</span>
            <nav aria-label="Tautan kaki halaman" class="flex flex-wrap gap-x-5 gap-y-2">
                <a href="{{ route('home') }}#publikasi">Publikasi</a>
                <a href="{{ route('guide') }}">Panduan</a>
                <a href="{{ route('privacy') }}">Privasi</a>
                <a href="{{ route('contact') }}">Kontak</a>
            </nav>
        </div>
    </footer>
</body>
</html>
