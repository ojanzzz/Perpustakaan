<x-layouts.app title="Publikasi — E-Perpustakaan Digital KPU" description="Publikasi digital E-Perpustakaan KPU.">
    <section id="publikasi" class="publication-page" aria-labelledby="publication-title">
        <div class="portal-container">
            <header class="publication-intro">
                <h1 id="publication-title">Publikasi</h1>
                <form action="{{ route('catalog.index') }}" method="GET" class="publication-search" role="search" data-autocomplete-form>
                    <label for="publication-search" class="sr-only">Cari publikasi</label>
                    <x-public.icon name="search" />
                    <input id="publication-search" name="q" autocomplete="off" data-autocomplete-input placeholder="Cari publikasi" aria-label="Cari publikasi">
                    <button type="submit">Cari</button>
                    <div class="autocomplete-panel" data-autocomplete-panel hidden></div>
                </form>
            </header>

            @if($latestBooks->isNotEmpty())
                <section class="publication-grid" aria-label="Daftar publikasi">
                    @foreach($latestBooks as $book)
                        <x-public.book-card :book="$book" />
                    @endforeach
                </section>

                <div class="publication-pagination" aria-label="Navigasi halaman publikasi">
                    {{ $latestBooks->onEachSide(1)->links() }}
                </div>
            @else
                <div class="publication-empty">
                    <h2>Belum ada publikasi</h2>
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
