<!doctype html>
<html lang="id" data-reader-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#303030">
    <title>{{ $book->title }} - Pembaca Digital</title>
    @vite(['resources/css/reader.css', 'resources/js/reader.js'])
    <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}" defer></script>
</head>
<body class="reader-body">
    <a class="reader-skip" href="#reader-stage">Lewati kontrol pembaca</a>

    <main
        id="reader-app"
        class="reader-app sidebar-closed"
        data-reader-root
        data-book-id="{{ $book->id }}"
        data-book-slug="{{ $book->slug }}"
        data-book-title="{{ $book->title }}"
        data-book-url="{{ route('books.show', $book) }}"
        data-document-url="{{ $documentUrl }}"
        data-initial-page="{{ $initialPage }}"
        data-explicit-page="{{ request()->has('page') ? 'true' : 'false' }}"
        data-saved-page="{{ $progress?->last_page ?: '' }}"
        data-total-pages="{{ max(1, (int) $book->page_count) }}"
        data-can-download="{{ $book->download_enabled ? 'true' : 'false' }}"
        data-can-print="{{ $book->print_enabled ? 'true' : 'false' }}"
        data-download-url="{{ $downloadUrl }}"
        data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
        data-favorited="{{ $favorited ? 'true' : 'false' }}"
        data-progress-url="{{ url('/api/member/books/'.$book->id.'/progress') }}"
        data-favorite-url="{{ url('/api/member/books/'.$book->id.'/favorite') }}"
        data-bookmark-url="{{ url('/api/member/books/'.$book->id.'/bookmarks') }}"
        data-analytics-url="{{ url('/api/books/'.$book->id.'/view') }}"
    >
        <header class="reader-control-bar" aria-label="Kontrol dokumen">
            <h1 class="sr-only">{{ $book->title }} — E-Perpustakaan Digital KPU</h1>

            <div class="reader-control-group reader-control-primary">
                <a class="reader-tool" href="{{ route('books.show', $book) }}" aria-label="Kembali ke detail buku">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg><span>Kembali</span>
                </a>
                <button class="reader-tool" type="button" data-action="toggle-sidebar" aria-label="Panel dokumen" aria-controls="reader-sidebar" aria-expanded="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span>Panel</span>
                </button>
                @if($book->download_enabled)
                    <a class="reader-tool reader-tool-optional" href="{{ $downloadUrl }}" data-download aria-label="Unduh PDF">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 21h14"/></svg><span>Unduh</span>
                    </a>
                @endif
                <button class="reader-tool reader-tool-optional" type="button" data-action="share" aria-label="Bagikan halaman">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4m-6.8 7 6.8 4"/></svg><span>Bagikan</span>
                </button>
            </div>

            <div class="reader-control-group reader-zoom-controls" aria-label="Perbesaran">
                <button class="reader-tool reader-tool-icon" type="button" data-action="zoom-out" aria-label="Perkecil">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M8 11h6m2 5 5 5"/></svg>
                </button>
                <label class="reader-zoom-range"><span class="sr-only">Perbesaran</span><input data-zoom-range type="range" min="50" max="300" step="5" value="100"></label>
                <button class="reader-tool reader-tool-icon" type="button" data-action="zoom-in" aria-label="Perbesar">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M8 11h6m-3-3v6m5 2 5 5"/></svg>
                </button>
                <span class="reader-zoom-label" data-zoom-label>100%</span>
                <button class="reader-tool reader-tool-icon reader-tool-optional" type="button" data-action="fullscreen" aria-label="Layar penuh">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3H3v4m14-4h4v4M7 21H3v-4m14 4h4v-4"/></svg>
                </button>
            </div>

            <div class="reader-control-group reader-page-controls" aria-label="Navigasi halaman">
                <button class="reader-tool reader-tool-icon" type="button" data-action="previous" aria-label="Halaman sebelumnya">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <label class="reader-page-input"><span class="sr-only">Nomor halaman</span><input data-page-input type="number" min="1" value="{{ $initialPage }}"><span>/ <b data-page-total>{{ max(1, (int) $book->page_count) }}</b></span></label>
                <button class="reader-tool reader-tool-icon" type="button" data-action="next" aria-label="Halaman berikutnya">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
                <button class="reader-tool reader-tool-icon" type="button" data-action="more" aria-label="Menu lainnya" aria-controls="reader-more-menu" aria-expanded="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
                </button>
            </div>
        </header>

        <aside id="reader-sidebar" class="reader-sidebar" aria-label="Alat dokumen" aria-hidden="true" inert>
            <div class="reader-tabs" role="tablist" aria-label="Panel dokumen">
                @foreach(['outline'=>'Daftar Isi','search'=>'Cari','bookmarks'=>'Bookmark'] as $tab => $label)
                    <button type="button" role="tab" data-tab="{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
            <section class="reader-panel is-active" data-panel="outline" aria-label="Daftar isi">
                <div class="reader-panel-heading"><strong>Daftar isi</strong><span>Struktur dokumen</span></div>
                <div class="outline-list" data-outline-list><p class="reader-muted">Memuat daftar isi…</p></div>
            </section>
            <section class="reader-panel" data-panel="search" aria-label="Pencarian dokumen">
                <form class="reader-search-form" data-search-form><label for="reader-search">Cari dalam dokumen</label><div><input id="reader-search" type="search" minlength="2" placeholder="Masukkan kata kunci"><button type="submit">Cari</button></div></form>
                <div class="search-results" data-search-results><p class="reader-muted">Hasil pencarian akan tampil di sini.</p></div>
            </section>
            <section class="reader-panel" data-panel="bookmarks" aria-label="Bookmark">
                <div class="reader-panel-heading"><strong>Bookmark</strong><button type="button" data-action="add-bookmark">+ Tandai</button></div>
                <div class="bookmark-list" data-bookmark-list></div>
            </section>
        </aside>

        <section id="reader-stage" class="reader-stage" aria-label="Halaman dokumen" tabindex="-1">
            <div class="reader-loading" data-loading role="status"><span></span><strong>Menyiapkan dokumen…</strong><small>PDF dimuat secara aman</small></div>
            <div class="reader-error" data-error hidden role="alert"><strong>Dokumen tidak dapat ditampilkan.</strong><p data-error-message>Periksa koneksi lalu coba kembali.</p><button type="button" data-action="retry">Coba lagi</button></div>
            <div class="flip-viewport" data-flip-viewport hidden></div>
            <div class="scroll-viewport" data-scroll-viewport hidden></div>
            <button class="page-edge page-edge-left" type="button" data-action="previous" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
            <button class="page-edge page-edge-right" type="button" data-action="next" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
        </section>

        <nav class="reader-filmstrip" aria-label="Thumbnail halaman">
            <button class="filmstrip-scroll" type="button" data-action="scroll-thumbnails-prev" aria-label="Geser thumbnail ke kiri"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
            <div class="filmstrip-track" data-filmstrip-track data-thumbnail-list></div>
            <button class="filmstrip-scroll" type="button" data-action="scroll-thumbnails-next" aria-label="Geser thumbnail ke kanan"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
        </nav>

        <div class="reader-accessibility-status sr-only">
            <span data-mode-label>Mode flip</span>
            <span class="sr-only" data-status-text aria-live="polite">Halaman {{ $initialPage }} dari {{ max(1, (int) $book->page_count) }}</span>
        </div>

        <div id="reader-more-menu" class="reader-popover" data-more-menu role="menu" aria-label="Menu pembaca" hidden>
            <button type="button" data-action="zoom-out" role="menuitem">Perkecil</button>
            <button type="button" data-action="zoom-in" role="menuitem">Perbesar</button>
            <button type="button" data-action="fit-width" role="menuitem">Sesuaikan lebar</button>
            <button type="button" data-action="fit-page" role="menuitem">Sesuaikan halaman</button>
            <button type="button" data-action="toggle-spread" role="menuitem">Satu atau dua halaman</button>
            <button type="button" data-action="fullscreen" role="menuitem">Layar penuh</button>
            <button type="button" data-action="share" role="menuitem">Bagikan</button>
            <button type="button" data-action="add-bookmark" role="menuitem">Tandai halaman</button>
            <button type="button" data-action="favorite" role="menuitem" aria-pressed="{{ $favorited ? 'true' : 'false' }}">Favorit</button>
            <button type="button" data-action="theme" role="menuitem">Mode terang/gelap</button>
            <button type="button" data-action="reduced-motion" role="menuitem">Kurangi animasi</button>
            @if($book->download_enabled)<a href="{{ $downloadUrl }}" data-download role="menuitem">Unduh PDF</a>@endif
            @if($book->print_enabled)<button type="button" data-action="print" role="menuitem">Cetak</button>@endif
            <a href="{{ route('guide') }}" target="_blank" role="menuitem">Bantuan pembaca</a>
        </div>

        <dialog class="reader-dialog" data-share-dialog aria-labelledby="share-title">
            <form method="dialog"><button class="reader-dialog-close" aria-label="Tutup">×</button></form>
            <div class="share-grid"><div><h2 id="share-title">Bagikan buku</h2><p>Bagikan halaman yang sedang dibaca atau pindai kode QR.</p><label for="share-url">Tautan halaman</label><div class="share-copy"><input id="share-url" data-share-url readonly><button type="button" data-action="copy-link">Salin</button></div><div class="share-links"><a data-share="whatsapp" target="_blank" rel="noopener">WhatsApp</a><a data-share="facebook" target="_blank" rel="noopener">Facebook</a><a data-share="x" target="_blank" rel="noopener">X</a><a data-share="email">Email</a></div></div><div class="share-qr" data-qr aria-label="Kode QR tautan buku"></div></div>
        </dialog>

        <div class="reader-toast" data-toast role="status" aria-live="polite" hidden></div>
    </main>

    <script id="reader-bookmarks-data" type="application/json">@json($bookmarks->map->only(['page', 'label', 'note'])->values())</script>
</body>
</html>
