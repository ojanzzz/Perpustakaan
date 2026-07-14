<!doctype html>
<html lang="id" data-reader-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - Pembaca Digital</title>
    @vite(['resources/css/reader.css', 'resources/js/reader.js'])
    <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}" defer></script>
</head>
<body class="reader-body">
    <a class="reader-skip" href="#reader-stage">Lewati alat pembaca</a>
    <main
        id="reader-app"
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
        class="reader-app"
    >
        <header class="reader-topbar" aria-label="Toolbar pembaca">
            <div class="reader-brand-block">
                <a class="reader-icon-button" href="{{ route('books.show', $book) }}" aria-label="Kembali ke detail buku" title="Kembali">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <button class="reader-icon-button" type="button" data-action="toggle-sidebar" aria-controls="reader-sidebar" aria-expanded="true" title="Panel navigasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></svg>
                </button>
                <div class="reader-title-wrap"><strong>{{ $book->title }}</strong><span>Pembaca Digital KPU</span></div>
            </div>

            <div class="reader-page-controls" aria-label="Navigasi halaman">
                <button class="reader-icon-button" type="button" data-action="previous" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
                <label class="reader-page-input"><span class="sr-only">Nomor halaman</span><input data-page-input type="number" min="1" value="{{ $initialPage }}"><span>/ <b data-page-total>{{ max(1, (int) $book->page_count) }}</b></span></label>
                <button class="reader-icon-button" type="button" data-action="next" aria-label="Halaman berikutnya"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
            </div>

            <div class="reader-tools" aria-label="Alat tampilan">
                <button class="reader-icon-button desktop-tool" type="button" data-action="zoom-out" aria-label="Perkecil"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M8 11h6m2 5 5 5"/></svg></button>
                <span class="reader-zoom desktop-tool" data-zoom-label>100%</span>
                <button class="reader-icon-button desktop-tool" type="button" data-action="zoom-in" aria-label="Perbesar"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M8 11h6m-3-3v6m5 2 5 5"/></svg></button>
                <button class="reader-text-button desktop-tool" type="button" data-action="fit-width">Lebar</button>
                <button class="reader-text-button desktop-tool" type="button" data-action="fit-page">Halaman</button>
                <button class="reader-icon-button" type="button" data-action="toggle-mode" aria-label="Ubah mode baca" title="Mode flip / scroll"><svg viewBox="0 0 24 24"><path d="M4 5h6a3 3 0 0 1 3 3v11a3 3 0 0 0-3-3H4z"/><path d="M20 5h-6a3 3 0 0 0-3 3v11a3 3 0 0 1 3-3h6z"/></svg></button>
                <button class="reader-icon-button desktop-tool" type="button" data-action="toggle-spread" aria-label="Satu atau dua halaman"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="8" height="14" rx="1"/><rect x="13" y="5" width="8" height="14" rx="1"/></svg></button>
                <button class="reader-icon-button" type="button" data-action="favorite" aria-pressed="{{ $favorited ? 'true' : 'false' }}" aria-label="Favorit"><svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8z"/></svg></button>
                <button class="reader-icon-button" type="button" data-action="share" aria-label="Bagikan"><svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4m-6.8 7 6.8 4"/></svg></button>
                <button class="reader-icon-button desktop-tool" type="button" data-action="fullscreen" aria-label="Layar penuh"><svg viewBox="0 0 24 24"><path d="M8 3H3v5m13-5h5v5M8 21H3v-5m13 5h5v-5"/></svg></button>
                <button class="reader-icon-button" type="button" data-action="more" aria-label="Menu lainnya" aria-expanded="false"><svg viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg></button>
            </div>
        </header>

        <aside id="reader-sidebar" class="reader-sidebar" aria-label="Navigasi dokumen">
            <div class="reader-tabs" role="tablist" aria-label="Panel dokumen">
                @foreach(['thumbnails'=>'Thumbnail','outline'=>'Daftar Isi','search'=>'Cari','bookmarks'=>'Bookmark'] as $tab => $label)
                    <button type="button" role="tab" data-tab="{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
            <section class="reader-panel is-active" data-panel="thumbnails" aria-label="Thumbnail halaman"><div class="thumbnail-list" data-thumbnail-list></div></section>
            <section class="reader-panel" data-panel="outline" aria-label="Daftar isi"><div class="reader-panel-heading"><strong>Daftar isi</strong><span>Struktur dokumen</span></div><div class="outline-list" data-outline-list><p class="reader-muted">Memuat daftar isi…</p></div></section>
            <section class="reader-panel" data-panel="search" aria-label="Pencarian dokumen"><form class="reader-search-form" data-search-form><label for="reader-search">Cari dalam dokumen</label><div><input id="reader-search" type="search" minlength="2" placeholder="Masukkan kata kunci"><button type="submit">Cari</button></div></form><div class="search-results" data-search-results><p class="reader-muted">Hasil pencarian akan tampil di sini.</p></div></section>
            <section class="reader-panel" data-panel="bookmarks" aria-label="Bookmark"><div class="reader-panel-heading"><strong>Bookmark</strong><button type="button" data-action="add-bookmark">+ Tandai halaman</button></div><div class="bookmark-list" data-bookmark-list></div></section>
        </aside>

        <section id="reader-stage" class="reader-stage" aria-label="Halaman dokumen" tabindex="-1">
            <div class="reader-loading" data-loading role="status"><span></span><strong>Menyiapkan dokumen…</strong><small>PDF dimuat secara aman</small></div>
            <div class="reader-error" data-error hidden role="alert"><strong>Dokumen tidak dapat ditampilkan.</strong><p data-error-message>Periksa koneksi lalu coba kembali.</p><button type="button" data-action="retry">Coba lagi</button></div>
            <div class="flip-viewport" data-flip-viewport hidden><div class="flip-spread" data-flip-spread></div></div>
            <div class="scroll-viewport" data-scroll-viewport hidden></div>
            <button class="page-edge page-edge-left" type="button" data-action="previous" aria-label="Halaman sebelumnya"></button>
            <button class="page-edge page-edge-right" type="button" data-action="next" aria-label="Halaman berikutnya"></button>
        </section>

        <footer class="reader-statusbar">
            <span data-status-text>Halaman {{ $initialPage }} dari {{ max(1, (int) $book->page_count) }}</span>
            <span data-mode-label>Mode flip</span>
            <span class="reader-status-help">← → navigasi · +/- zoom · F fullscreen</span>
        </footer>

        <div class="reader-popover" data-more-menu hidden>
            <button type="button" data-action="theme">Mode terang/gelap</button>
            <button type="button" data-action="reduced-motion">Kurangi animasi</button>
            @if($book->download_enabled)<a href="{{ $downloadUrl }}" data-download>Unduh PDF</a>@endif
            @if($book->print_enabled)<button type="button" data-action="print">Cetak</button>@endif
            <a href="{{ route('guide') }}" target="_blank">Bantuan pembaca</a>
        </div>

        <dialog class="reader-dialog" data-share-dialog aria-labelledby="share-title">
            <form method="dialog"><button class="reader-dialog-close" aria-label="Tutup">×</button></form>
            <div class="share-grid"><div><h2 id="share-title">Bagikan buku</h2><p>Bagikan halaman yang sedang dibaca atau pindai kode QR.</p><label for="share-url">Tautan halaman</label><div class="share-copy"><input id="share-url" data-share-url readonly><button type="button" data-action="copy-link">Salin tautan</button></div><div class="share-links"><a data-share="whatsapp" target="_blank" rel="noopener">WhatsApp</a><a data-share="facebook" target="_blank" rel="noopener">Facebook</a><a data-share="x" target="_blank" rel="noopener">X</a><a data-share="email">Email</a></div></div><div class="share-qr" data-qr aria-label="Kode QR tautan buku"></div></div>
        </dialog>

        <div class="reader-toast" data-toast role="status" aria-live="polite" hidden></div>
    </main>
    <script id="reader-bookmarks-data" type="application/json">@json($bookmarks->map->only(['page', 'label', 'note'])->values())</script>
</body>
</html>
