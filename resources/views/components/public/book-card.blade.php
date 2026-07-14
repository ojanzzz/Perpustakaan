@props(['book', 'compact' => false])
<article class="book-card group">
    <a href="{{ route('books.show', $book) }}" class="block" aria-label="Lihat detail {{ $book->title }}">
        <x-public.book-cover :book="$book" />
        <div class="mt-4">
            <h3 class="line-clamp-2 text-[15px] font-bold leading-5 text-navy group-hover:text-red-700">{{ $book->title }}</h3>
            @if(!$compact)<p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $book->authors->pluck('name')->join(', ') ?: ($book->publisher?->name ?: 'Publikasi KPU') }}</p>@endif
            <p class="mt-1 text-xs font-medium text-slate-500">{{ $book->publication_year ?: 'Tanpa tahun' }}</p>
        </div>
    </a>
</article>
