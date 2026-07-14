@props(['items' => []])
<nav aria-label="Breadcrumb" class="portal-container py-4 text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-2"><li><a href="{{ route('home') }}" class="hover:text-red-700">Beranda</a></li>@foreach($items as $label => $url)<li aria-hidden="true">/</li><li>@if($url)<a href="{{ $url }}" class="hover:text-red-700">{{ $label }}</a>@else<span aria-current="page" class="text-slate-700">{{ $label }}</span>@endif</li>@endforeach</ol>
</nav>

