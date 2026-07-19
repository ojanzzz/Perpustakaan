@props(['book', 'class' => ''])
<div {{ $attributes->merge(['class' => 'book-cover '.$class]) }}>
    <span class="book-cover-pages" aria-hidden="true"></span>
    <div class="book-cover-face">
        @if($book->coverUrl())
            <img src="{{ $book->coverUrl() }}" alt="Sampul {{ $book->title }}" loading="lazy" width="360" height="510">
        @else
            <div class="book-cover-fallback" aria-hidden="true">
                <span class="cover-rule"></span><span class="cover-edition">E-PERPUSTAKAAN</span>
                <strong>{{ $book->title }}</strong><small>{{ $book->publication_year ?: 'Publikasi digital' }}</small>
            </div>
        @endif
    </div>
</div>
