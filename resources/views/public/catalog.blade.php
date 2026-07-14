<x-layouts.app :title="$heading.' — E-Perpustakaan Digital KPU'" :description="$description">
    <x-public.breadcrumb :items="[$heading => null]" />
    <section class="portal-container pb-16 pt-4">
        <div class="max-w-3xl"><h1 class="page-title">{{ $heading }}</h1><p class="page-lead">{{ $description }}</p></div>
        <form action="{{ route('catalog.index') }}" method="GET" class="catalog-search mt-7" role="search" data-autocomplete-form>
            <label class="sr-only" for="catalog-q">Cari katalog</label><x-public.icon name="search" class="size-5 text-slate-400"/><input id="catalog-q" name="q" value="{{ $filters['q'] ?? '' }}" autocomplete="off" data-autocomplete-input placeholder="Cari judul, penulis, penerbit, ISBN, kategori, atau topik"><button class="button-primary">Cari</button><div class="autocomplete-panel" data-autocomplete-panel hidden></div>
        </form>
        <div class="mt-7 lg:grid lg:grid-cols-[270px_1fr] lg:gap-8">
            <button type="button" class="button-secondary mb-5 w-full justify-center lg:hidden" data-filter-toggle aria-expanded="false" aria-controls="catalog-filters"><x-public.icon name="filter"/> Filter katalog</button>
            <aside id="catalog-filters" class="filter-panel" data-filter-panel>
                <form action="{{ route('catalog.index') }}" method="GET" class="space-y-4">
                    @if(!empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
                    <input type="hidden" name="mode" value="{{ $mode }}"><div class="flex items-center justify-between"><h2 class="text-lg font-bold text-navy">Filter</h2><a href="{{ route('catalog.index') }}" class="text-xs font-semibold text-red-700">Reset</a></div>
                    <label class="filter-label" id="filter-kategori">Kategori<select name="category"><option value="">Semua kategori</option>@foreach($categories as $item)<option value="{{ $item->id }}" @selected(($filters['category'] ?? null)==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
                    <label class="filter-label">Koleksi<select name="collection"><option value="">Semua koleksi</option>@foreach($collections as $item)<option value="{{ $item->id }}" @selected(($filters['collection'] ?? null)==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
                    <label class="filter-label">Penulis<select name="author"><option value="">Semua penulis</option>@foreach($authors as $item)<option value="{{ $item->id }}" @selected(($filters['author'] ?? null)==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
                    <label class="filter-label">Penerbit<select name="publisher"><option value="">Semua penerbit</option>@foreach($publishers as $item)<option value="{{ $item->id }}" @selected(($filters['publisher'] ?? null)==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
                    <div class="grid grid-cols-2 gap-3"><label class="filter-label">Dari tahun<input name="year_from" type="number" value="{{ $filters['year_from'] ?? '' }}"></label><label class="filter-label">Sampai<input name="year_to" type="number" value="{{ $filters['year_to'] ?? '' }}"></label></div>
                    <label class="filter-label">Bahasa<select name="language"><option value="">Semua bahasa</option>@foreach($languages as $item)<option value="{{ $item->id }}" @selected(($filters['language'] ?? null)==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
                    <label class="filter-label">Jenis publikasi<select name="publication_type"><option value="">Semua jenis</option>@foreach($publicationTypes as $item)<option value="{{ $item }}" @selected(($filters['publication_type'] ?? null)===$item)>{{ $item }}</option>@endforeach</select></label>
                    <button class="button-navy w-full justify-center">Terapkan filter</button>
                </form>
            </aside>
            <div class="min-w-0">
                <div class="catalog-toolbar"><p><strong>{{ number_format($books->total()) }}</strong> hasil ditemukan</p><div class="flex flex-wrap items-center gap-2"><label class="sr-only" for="sort">Urutkan</label><form method="GET" data-auto-submit>@foreach(request()->except(['sort','page']) as $key=>$value)@if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach<select id="sort" name="sort" class="toolbar-select"><option value="custom">Urutan khusus</option>@foreach(['newest'=>'Terbaru','oldest'=>'Terlama','title_asc'=>'Judul A–Z','title_desc'=>'Judul Z–A','popular'=>'Paling banyak dibaca','downloaded'=>'Paling banyak diunduh'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['sort'] ?? 'custom')===$value)>{{ $label }}</option>@endforeach</select></form><div class="view-switcher" aria-label="Mode tampilan">@foreach(['grid'=>'grid','list'=>'list','shelf'=>'shelf'] as $value=>$icon)<a href="{{ route('catalog.index', array_merge(request()->query(), ['mode'=>$value])) }}" @class(['active'=>$mode===$value]) aria-label="Mode {{ $value }}" @if($mode===$value) aria-current="true" @endif><x-public.icon :name="$icon"/></a>@endforeach</div></div></div>
                @if($books->isEmpty())<x-public.empty-state title="Tidak ada hasil ditemukan" description="Coba ubah kata kunci atau hapus beberapa filter untuk menemukan koleksi yang relevan." />
                @elseif($mode === 'list')<div class="book-list">@foreach($books as $book)<article><x-public.book-cover :book="$book"/><div><h2><a href="{{ route('books.show',$book) }}">{{ $book->title }}</a></h2><p class="book-byline">{{ $book->authors->pluck('name')->join(', ') ?: $book->publisher?->name }}</p><p class="line-clamp-2 text-sm leading-6 text-slate-600">{{ $book->description }}</p><small>{{ $book->publication_year }} · {{ $book->language?->name }} · {{ number_format($book->views_count) }} dibaca</small></div></article>@endforeach</div>
                @elseif($mode === 'shelf')<div class="digital-shelf"><div class="shelf-books">@foreach($books as $book)<x-public.book-card :book="$book" />@endforeach</div><div class="shelf-line" aria-hidden="true"></div></div>
                @else<div class="book-grid catalog-grid">@foreach($books as $book)<x-public.book-card :book="$book" />@endforeach</div>@endif
                <div class="mt-10">{{ $books->links() }}</div>
            </div>
        </div>
    </section>
</x-layouts.app>

