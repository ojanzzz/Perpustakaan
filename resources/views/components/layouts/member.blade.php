@props(['title', 'heading', 'description' => null])
<x-layouts.app :title="$title.' — E-Perpustakaan Digital KPU'">
    <x-public.breadcrumb :items="[$heading => null]" />
    <div class="portal-container pb-16 pt-3">
        <div class="member-heading"><div><h1>{{ $heading }}</h1>@if($description)<p>{{ $description }}</p>@endif</div><span>{{ auth()->user()->name }}</span></div>
        @if(session('status'))<div class="member-alert" role="status">{{ session('status') }}</div>@endif
        <div class="member-layout">
            <nav class="member-nav" aria-label="Navigasi akun">
                @foreach([
                    ['member.profile','Profil'],['member.favorites','Favorit'],['member.history','Riwayat baca'],
                    ['member.bookmarks','Bookmark'],['member.collections','Koleksi saya'],
                    ['member.subscriptions','Langganan'],['member.notifications','Notifikasi']
                ] as [$routeName,$label])
                    <a href="{{ route($routeName) }}" @class(['is-active' => request()->routeIs($routeName)])>{{ $label }}</a>
                @endforeach
            </nav>
            <section class="member-content">{{ $slot }}</section>
        </div>
    </div>
</x-layouts.app>
