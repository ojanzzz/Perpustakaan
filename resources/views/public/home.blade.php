<x-layouts.app title="E-Perpustakaan Digital KPU" description="Pusat literasi digital untuk menemukan publikasi dan pengetahuan kepemiluan.">
    <section class="hero-section">
        <div class="portal-container hero-layout">
            <div class="hero-copy">
                <h1 class="max-w-3xl text-4xl font-bold tracking-[-.035em] text-navy sm:text-5xl lg:text-[60px] lg:leading-[1.06]">Pusat literasi digital kepemiluan</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">Temukan publikasi tepercaya seputar pemilu, demokrasi, kelembagaan, dan partisipasi warga dalam satu katalog yang mudah diakses.</p>
                <form action="{{ route('catalog.index') }}" method="GET" class="hero-search mt-8" role="search" data-autocomplete-form>
                    <label for="hero-search" class="sr-only">Cari buku atau dokumen</label>
                    <x-public.icon name="search" class="size-5 text-slate-400" />
                    <input id="hero-search" name="q" autocomplete="off" data-autocomplete-input placeholder="Cari judul, penulis, subjek, atau kata kunci" class="min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-slate-400">
                    <button class="button-primary">Cari</button>
                    <div class="autocomplete-panel" data-autocomplete-panel hidden></div>
                </form>
                <a href="{{ route('catalog.index') }}" class="button-primary mt-5"><x-public.icon name="book" /> Jelajahi katalog <x-public.icon name="arrow" /></a>
            </div>
            <div class="hero-art" aria-hidden="true">
                <img src="{{ asset('images/portal/hero-library.webp') }}" alt="" width="720" height="540">
            </div>
        </div>
    </section>

    <section class="portal-section category-section" aria-labelledby="kategori-utama">
        <div class="portal-container">
            <div class="section-heading"><div><h2 id="kategori-utama">Kategori utama</h2><p>Mulai dari topik yang paling relevan untuk Anda.</p></div><a href="{{ route('catalog.index') }}#filter-kategori">Lihat semua kategori <x-public.icon name="arrow" /></a></div>
            <div class="category-rail">
                @forelse($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="category-link"><span class="category-icon">{{ str_pad((string)($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $category->name }}</strong><small>{{ $category->accessible_books_count }} buku</small></a>
                @empty
                    <p class="empty-inline">Kategori belum tersedia.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="portal-section latest-section" aria-labelledby="koleksi-terbaru">
        <div class="portal-container">
            <div class="section-heading"><div><h2 id="koleksi-terbaru">Koleksi terbaru</h2><p>Publikasi yang baru ditambahkan ke katalog.</p></div><a href="{{ route('catalog.latest') }}">Lihat koleksi terbaru <x-public.icon name="arrow" /></a></div>
            <div class="book-grid home-book-rail">
                @forelse($latestBooks as $book)<x-public.book-card :book="$book" />@empty<x-public.empty-state title="Belum ada buku terbit" description="Koleksi terbaru akan tampil setelah publikasi tersedia." />@endforelse
            </div>
        </div>
    </section>

    <section class="portal-section discovery-section">
        <div class="portal-container home-discovery-grid">
            <div class="discovery-panel"><div class="section-heading compact"><div><h2>Paling banyak dibaca</h2><p>Publikasi yang paling sering dibuka pembaca.</p></div><a href="{{ route('catalog.popular') }}">Lihat semua <x-public.icon name="arrow" /></a></div>
                <ol class="popular-list">@forelse($popularBooks as $book)<li><span class="popular-rank">{{ $loop->iteration }}</span><x-public.book-cover :book="$book" class="mini-cover"/><div class="min-w-0 flex-1"><a href="{{ route('books.show', $book) }}" class="font-bold text-navy hover:text-red-700">{{ $book->title }}</a><p>{{ $book->publisher?->name ?: $book->authors->pluck('name')->join(', ') }}</p></div><span class="view-count">{{ number_format($book->views_count) }} dibaca</span></li>@empty<li class="empty-inline">Data popularitas belum tersedia.</li>@endforelse</ol>
            </div>
            <div class="discovery-panel"><div class="section-heading compact"><div><h2>Rak pilihan</h2><p>Koleksi terkurasi untuk penelusuran cepat.</p></div><a href="{{ route('catalog.index', ['mode'=>'shelf']) }}">Semua rak <x-public.icon name="arrow" /></a></div>
                <div class="shelf-list">@forelse($collections as $collection)<a href="{{ route('collections.show', $collection) }}"><span><x-public.icon name="shelf" /></span><div><strong>{{ $collection->name }}</strong><small>{{ $collection->accessible_books_count }} buku</small></div><x-public.icon name="chevron" class="ml-auto size-5" /></a>@empty<p class="empty-inline">Rak belum tersedia.</p>@endforelse</div>
            </div>
        </div>
    </section>

    <section class="portal-container statistics-section" aria-label="Statistik perpustakaan">
        <div class="statistics-band">@foreach([['Koleksi', $statistics['books']], ['Kategori', $statistics['categories']], ['Rak digital', $statistics['collections']], ['Kunjungan', $statistics['views']]] as [$label,$value])<div><strong>{{ number_format($value) }}</strong><span>{{ $label }}</span></div>@endforeach</div>
    </section>

    @if($announcement)<section class="portal-container announcement-section"><div class="announcement-band"><span class="announcement-icon" aria-hidden="true"><x-public.icon name="announcement" /></span><div><small>Pengumuman</small><h2>{{ $announcement->title }}</h2><p>{{ $announcement->content }}</p></div>@if($announcement->link)<a href="{{ $announcement->link }}" class="button-secondary">Selengkapnya <x-public.icon name="arrow" /></a>@endif</div></section>@endif
</x-layouts.app>
