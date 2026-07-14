@props(['title', 'description'])
<div class="empty-state"><span aria-hidden="true"><x-public.icon name="search" class="size-9" /></span><h2>{{ $title }}</h2><p>{{ $description }}</p><a href="{{ route('catalog.index') }}" class="button-secondary mt-5">Bersihkan filter</a></div>

