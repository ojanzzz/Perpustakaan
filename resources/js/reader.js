const root = document.querySelector('[data-reader-root]');

if (root) {
    const state = {
        pdf: null,
        page: Number((root.dataset.explicitPage === 'true' ? root.dataset.initialPage : root.dataset.savedPage) || root.dataset.initialPage || 1),
        total: Number(root.dataset.totalPages || 1),
        zoom: 1,
        fit: 'page',
        mode: window.matchMedia('(max-width: 767px)').matches ? 'scroll' : 'flip',
        spread: !window.matchMedia('(max-width: 1100px)').matches,
        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        sidebar: !window.matchMedia('(max-width: 767px)').matches,
        renderedScroll: new Map(),
        pagePromises: new Map(),
        bookmarks: JSON.parse(document.querySelector('#reader-bookmarks-data')?.textContent || '[]'),
        startedAt: Date.now(),
        lastProgressAt: Date.now(),
        lastAnalyticsAt: Date.now(),
        readerSession: sessionStorage.getItem('reader:session') || (crypto.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`),
    };

    const $ = (selector, scope = root) => scope.querySelector(selector);
    const $$ = (selector, scope = root) => [...scope.querySelectorAll(selector)];
    const loading = $('[data-loading]');
    const errorBox = $('[data-error]');
    const flipViewport = $('[data-flip-viewport]');
    const flipSpread = $('[data-flip-spread]');
    const scrollViewport = $('[data-scroll-viewport]');
    const sidebar = $('#reader-sidebar');
    const stage = $('#reader-stage');
    const pageInput = $('[data-page-input]');
    const toast = $('[data-toast]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let toastTimer;
    let progressTimer;
    let scrollObserver;
    let scrollNavigationUntil = 0;
    let scrollAlignmentTimers = [];
    let flipRenderToken = 0;
    let pageTurnCleanupTimer;

    root.classList.toggle('sidebar-closed', !state.sidebar);
    root.classList.toggle('reduce-motion', state.reduced);
    sessionStorage.setItem('reader:session', state.readerSession);

    const icons = {
        bookmark: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg>',
    };

    function notify(message) {
        clearTimeout(toastTimer);
        toast.textContent = message;
        toast.hidden = false;
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        toastTimer = setTimeout(() => {
            toast.classList.remove('is-visible');
            setTimeout(() => { toast.hidden = true; }, 180);
        }, 2600);
    }

    function showError(error) {
        loading.hidden = true;
        flipViewport.hidden = true;
        scrollViewport.hidden = true;
        errorBox.hidden = false;
        $('[data-error-message]').textContent = error?.message || 'Periksa koneksi lalu coba kembali.';
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...options.headers},
            ...options,
        });
        if (!response.ok) throw new Error(response.status === 401 ? 'Silakan masuk sebagai anggota.' : 'Perubahan tidak dapat disimpan.');
        return response.status === 204 ? null : response.json();
    }

    async function getPage(number) {
        if (!state.pagePromises.has(number)) state.pagePromises.set(number, state.pdf.getPage(number));
        return state.pagePromises.get(number);
    }

    function availableScale(page, spread = false) {
        const base = page.getViewport({scale: 1});
        const sidebarWidth = state.sidebar && window.innerWidth > 767 ? sidebar.offsetWidth : 0;
        const width = Math.max(260, window.innerWidth - sidebarWidth - 72) / (spread ? 2 : 1);
        const height = Math.max(340, window.innerHeight - 132);
        const fitWidth = (width - 28) / base.width;
        const fitPage = Math.min(fitWidth, (height - 32) / base.height);
        return (state.fit === 'width' ? fitWidth : fitPage) * state.zoom;
    }

    async function canvasForPage(number, context = 'page') {
        const page = await getPage(number);
        const scale = context === 'thumbnail' ? 0.2 : availableScale(page, context === 'spread');
        const viewport = page.getViewport({scale: Math.max(0.1, scale)});
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width * ratio);
        canvas.height = Math.floor(viewport.height * ratio);
        canvas.style.width = `${Math.floor(viewport.width)}px`;
        canvas.style.height = `${Math.floor(viewport.height)}px`;
        canvas.setAttribute('aria-label', `Halaman ${number}`);
        const context2d = canvas.getContext('2d', {alpha: false});
        await page.render({canvasContext: context2d, viewport, transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0]}).promise;
        return canvas;
    }

    function updateChrome() {
        pageInput.value = state.page;
        $('[data-page-total]').textContent = state.total;
        $('[data-status-text]').textContent = `Halaman ${state.page} dari ${state.total}`;
        $('[data-mode-label]').textContent = state.mode === 'flip' ? `Mode flip · ${state.spread ? 'dua halaman' : 'satu halaman'}` : 'Mode scroll';
        $('[data-zoom-label]').textContent = `${Math.round(state.zoom * 100)}%`;
        const url = new URL(window.location.href);
        url.searchParams.set('page', state.page);
        history.replaceState({}, '', url);
        localStorage.setItem(`reader:last-page:${root.dataset.bookSlug}`, String(state.page));
        $$('.thumbnail-item').forEach(item => item.classList.toggle('is-current', Number(item.dataset.page) === state.page));
    }

    async function renderFlip(direction = 'none') {
        const renderToken = ++flipRenderToken;
        root.dataset.pageTurnState = 'rendering';
        flipViewport.hidden = false;
        scrollViewport.hidden = true;
        const previousWrapper = $('.flip-pages', flipSpread);
        const pages = [state.page];
        if (state.spread && state.page < state.total) pages.push(state.page + 1);
        const wrapper = document.createElement('div');
        wrapper.className = `flip-pages ${state.spread ? 'is-spread' : 'is-single'}`;
        for (const number of pages) {
            const sheet = document.createElement('article');
            sheet.className = 'flip-sheet';
            sheet.dataset.page = number;
            sheet.innerHTML = `<span class="page-loading">Memuat halaman ${number}…</span>`;
            wrapper.append(sheet);
            try {
                const canvas = await canvasForPage(number, state.spread ? 'spread' : 'page');
                sheet.replaceChildren(canvas);
            } catch (error) {
                sheet.innerHTML = `<p class="page-failed">Halaman ${number} gagal dimuat.</p>`;
            }
        }
        if (renderToken !== flipRenderToken) return;
        clearTimeout(pageTurnCleanupTimer);
        if (state.reduced || direction === 'none' || !previousWrapper) {
            flipSpread.replaceChildren(wrapper);
            root.dataset.pageTurnState = 'idle';
        } else {
            const previousSheets = $$('.flip-sheet', previousWrapper);
            const turningSheet = direction === 'next' ? previousSheets.at(-1) : previousSheets[0];
            const wrapperBounds = previousWrapper.getBoundingClientRect();
            const sheetBounds = turningSheet.getBoundingClientRect();
            const overlay = document.createElement('div');
            overlay.className = `page-turn-overlay ${direction === 'next' ? 'turn-forward' : 'turn-backward'} ${previousWrapper.classList.contains('is-spread') ? 'is-spread' : 'is-single'}`;
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.width = `${wrapperBounds.width}px`;
            overlay.style.height = `${wrapperBounds.height}px`;
            turningSheet.classList.add('turning-sheet');
            turningSheet.style.width = `${sheetBounds.width}px`;
            turningSheet.style.height = `${sheetBounds.height}px`;
            overlay.append(turningSheet);
            flipSpread.replaceChildren(wrapper, overlay);
            root.dataset.pageTurnState = direction;

            const cleanup = () => {
                if (overlay.isConnected) overlay.remove();
                root.dataset.pageTurnState = 'complete';
            };
            overlay.addEventListener('animationend', cleanup, {once: true});
            requestAnimationFrame(() => requestAnimationFrame(() => overlay.classList.add('is-turning')));
            pageTurnCleanupTimer = setTimeout(cleanup, 1100);
        }
        updateChrome();
    }

    function setupScrollPages() {
        scrollViewport.replaceChildren();
        for (let number = 1; number <= state.total; number++) {
            const page = document.createElement('article');
            page.className = 'scroll-page';
            page.dataset.page = number;
            page.innerHTML = `<span class="page-loading">Halaman ${number}</span>`;
            scrollViewport.append(page);
        }
        scrollObserver?.disconnect();
        scrollObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                const number = Number(entry.target.dataset.page);
                if (entry.isIntersecting) {
                    renderScrollPage(number, entry.target);
                    if (Date.now() >= scrollNavigationUntil && entry.intersectionRatio > 0.55 && state.page !== number) {
                        state.page = number;
                        updateChrome();
                    }
                } else if (Math.abs(number - state.page) > 4 && state.renderedScroll.has(number)) {
                    entry.target.innerHTML = `<span class="page-loading">Halaman ${number}</span>`;
                    state.renderedScroll.delete(number);
                }
            });
        }, {root: scrollViewport, rootMargin: '50% 0px', threshold: [0.01, 0.55]});
        $$('.scroll-page', scrollViewport).forEach(page => scrollObserver.observe(page));
    }

    async function renderScrollPage(number, container) {
        if (state.renderedScroll.has(number)) return;
        state.renderedScroll.set(number, true);
        try {
            const canvas = await canvasForPage(number, 'page');
            container.replaceChildren(canvas);
        } catch {
            state.renderedScroll.delete(number);
            container.innerHTML = `<p class="page-failed">Halaman ${number} gagal dimuat.</p>`;
        }
    }

    async function showMode(scrollToPage = false) {
        if (state.mode === 'scroll') {
            flipViewport.hidden = true;
            scrollViewport.hidden = false;
            if (!scrollViewport.children.length) setupScrollPages();
            if (scrollToPage) {
                scheduleScrollAlignment(state.page);
            }
            updateChrome();
        } else {
            scrollObserver?.disconnect();
            await renderFlip();
        }
    }

    async function goToPage(page, direction = 'none') {
        state.page = Math.max(1, Math.min(Number(page) || 1, state.total));
        updateChrome();
        if (state.mode === 'scroll') {
            scheduleScrollAlignment(state.page, !state.reduced);
        } else {
            await renderFlip(direction);
        }
        scheduleProgress();
    }

    function scheduleScrollAlignment(page, smoothFirst = false) {
        scrollAlignmentTimers.forEach(clearTimeout);
        scrollAlignmentTimers = [];
        scrollNavigationUntil = Date.now() + 2600;
        [0, 250, 750, 1500, 2400].forEach((delay, index) => {
            scrollAlignmentTimers.push(setTimeout(() => {
                if (state.page !== page || state.mode !== 'scroll') return;
                scrollViewport.querySelector(`[data-page="${page}"]`)?.scrollIntoView({behavior: smoothFirst && index === 0 ? 'smooth' : 'auto', block: 'start'});
            }, delay));
        });
    }

    function setupThumbnails() {
        const list = $('[data-thumbnail-list]');
        const observer = new IntersectionObserver(entries => entries.forEach(async entry => {
            if (!entry.isIntersecting || entry.target.dataset.rendered) return;
            entry.target.dataset.rendered = 'true';
            const number = Number(entry.target.dataset.page);
            try {
                const canvas = await canvasForPage(number, 'thumbnail');
                $('.thumbnail-canvas', entry.target).replaceChildren(canvas);
            } catch { $('.thumbnail-canvas', entry.target).textContent = 'Gagal'; }
        }), {root: list, rootMargin: '100px'});
        for (let number = 1; number <= state.total; number++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'thumbnail-item';
            button.dataset.page = number;
            button.innerHTML = `<span class="thumbnail-canvas"><i></i></span><b>${number}</b>`;
            button.addEventListener('click', () => goToPage(number));
            list.append(button);
            observer.observe(button);
        }
    }

    async function setupOutline() {
        const container = $('[data-outline-list]');
        const outline = await state.pdf.getOutline();
        if (!outline?.length) {
            container.innerHTML = '<p class="reader-muted">Dokumen ini tidak memiliki daftar isi tertanam.</p>';
            return;
        }
        container.replaceChildren();
        const append = (items, depth = 0) => items.forEach(item => {
            const button = document.createElement('button');
            button.type = 'button'; button.textContent = item.title; button.style.setProperty('--outline-depth', depth);
            button.addEventListener('click', async () => {
                let destination = item.dest;
                if (typeof destination === 'string') destination = await state.pdf.getDestination(destination);
                if (!destination) return;
                const index = await state.pdf.getPageIndex(destination[0]);
                goToPage(index + 1);
            });
            container.append(button);
            if (item.items?.length) append(item.items, depth + 1);
        });
        append(outline);
    }

    function renderBookmarks() {
        const list = $('[data-bookmark-list]');
        if (!state.bookmarks.length) {
            list.innerHTML = '<p class="reader-muted">Belum ada halaman yang ditandai.</p>';
            return;
        }
        list.replaceChildren();
        state.bookmarks.sort((a, b) => a.page - b.page).forEach(bookmark => {
            const row = document.createElement('div');
            row.className = 'bookmark-row';
            row.innerHTML = `<button type="button">${icons.bookmark}<span><strong>${bookmark.label || `Halaman ${bookmark.page}`}</strong><small>Halaman ${bookmark.page}</small></span></button><button type="button" class="bookmark-delete" aria-label="Hapus bookmark">×</button>`;
            $('button', row).addEventListener('click', () => goToPage(bookmark.page));
            $('.bookmark-delete', row).addEventListener('click', () => deleteBookmark(bookmark.page));
            list.append(row);
        });
    }

    async function addBookmark() {
        if (state.bookmarks.some(item => item.page === state.page)) return notify('Halaman ini sudah ditandai.');
        const bookmark = {page: state.page, label: `Halaman ${state.page}`, note: null};
        if (root.dataset.authenticated === 'true') {
            try {
                const data = await api(root.dataset.bookmarkUrl, {method: 'POST', body: JSON.stringify(bookmark)});
                state.bookmarks.push(data.bookmark);
            } catch (error) { return notify(error.message); }
        } else {
            state.bookmarks.push(bookmark);
            localStorage.setItem(`reader:bookmarks:${root.dataset.bookSlug}`, JSON.stringify(state.bookmarks));
        }
        renderBookmarks(); notify('Halaman ditambahkan ke bookmark.');
    }

    async function deleteBookmark(page) {
        if (root.dataset.authenticated === 'true') {
            try { await api(`${root.dataset.bookmarkUrl}/${page}`, {method: 'DELETE'}); }
            catch (error) { return notify(error.message); }
        }
        state.bookmarks = state.bookmarks.filter(item => item.page !== page);
        localStorage.setItem(`reader:bookmarks:${root.dataset.bookSlug}`, JSON.stringify(state.bookmarks));
        renderBookmarks();
    }

    async function searchDocument(query) {
        const results = $('[data-search-results]');
        results.innerHTML = '<p class="reader-muted">Mencari di seluruh dokumen…</p>';
        const matches = [];
        for (let number = 1; number <= state.total; number++) {
            const page = await getPage(number);
            const content = await page.getTextContent();
            const text = content.items.map(item => item.str).join(' ');
            const index = text.toLocaleLowerCase('id').indexOf(query.toLocaleLowerCase('id'));
            if (index >= 0) matches.push({page: number, excerpt: text.slice(Math.max(0, index - 45), index + query.length + 70)});
        }
        if (!matches.length) { results.innerHTML = `<p class="reader-muted">Tidak ada hasil untuk “${escapeHtml(query)}”.</p>`; return; }
        results.replaceChildren();
        matches.forEach(match => {
            const button = document.createElement('button');
            button.type = 'button'; button.innerHTML = `<strong>Halaman ${match.page}</strong><span>${escapeHtml(match.excerpt)}</span>`;
            button.addEventListener('click', () => goToPage(match.page));
            results.append(button);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div'); div.textContent = text; return div.innerHTML;
    }

    function setupShare() {
        const dialog = $('[data-share-dialog]');
        const url = new URL(root.dataset.bookUrl);
        url.pathname = `${url.pathname}/baca`;
        url.searchParams.set('page', state.page);
        $('[data-share-url]').value = url.toString();
        const encodedUrl = encodeURIComponent(url.toString());
        const encodedTitle = encodeURIComponent(root.dataset.bookTitle);
        $('[data-share="whatsapp"]').href = `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`;
        $('[data-share="facebook"]').href = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
        $('[data-share="x"]').href = `https://x.com/intent/post?text=${encodedTitle}&url=${encodedUrl}`;
        $('[data-share="email"]').href = `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
        const qr = $('[data-qr]'); qr.replaceChildren();
        if (window.QRCode) new window.QRCode(qr, {text: url.toString(), width: 176, height: 176, colorDark: '#0f2747', colorLight: '#ffffff'});
        dialog.showModal();
    }

    function scheduleProgress() {
        clearTimeout(progressTimer);
        progressTimer = setTimeout(() => { saveProgress(); saveAnalytics(); }, 700);
    }

    async function saveProgress() {
        if (root.dataset.authenticated !== 'true') return;
        const now = Date.now();
        const delta = Math.max(0, Math.min(300, Math.round((now - state.lastProgressAt) / 1000)));
        state.lastProgressAt = now;
        try { await api(root.dataset.progressUrl, {method: 'PUT', body: JSON.stringify({page: state.page, duration_delta: delta})}); } catch { /* retry on next interaction */ }
    }

    async function saveAnalytics(initial = false) {
        const now = Date.now();
        const delta = initial ? 0 : Math.max(0, Math.min(300, Math.round((now - state.lastAnalyticsAt) / 1000)));
        state.lastAnalyticsAt = now;
        try {
            await api(root.dataset.analyticsUrl, {method: 'POST', body: JSON.stringify({session_key: state.readerSession, page: state.page, duration_delta: delta})});
        } catch { /* analytics must never interrupt reading */ }
    }

    async function perform(action) {
        switch (action) {
            case 'previous': return goToPage(state.page - (state.mode === 'flip' && state.spread ? 2 : 1), 'previous');
            case 'next': return goToPage(state.page + (state.mode === 'flip' && state.spread ? 2 : 1), 'next');
            case 'zoom-in': state.zoom = Math.min(3, state.zoom + 0.15); return showMode();
            case 'zoom-out': state.zoom = Math.max(0.5, state.zoom - 0.15); return showMode();
            case 'fit-width': state.fit = 'width'; state.zoom = 1; return showMode();
            case 'fit-page': state.fit = 'page'; state.zoom = 1; return showMode();
            case 'toggle-mode': state.mode = state.mode === 'flip' ? 'scroll' : 'flip'; return showMode(true);
            case 'toggle-spread': state.spread = !state.spread; state.mode = 'flip'; return showMode();
            case 'toggle-sidebar': state.sidebar = !state.sidebar; root.classList.toggle('sidebar-closed', !state.sidebar); $('[data-action="toggle-sidebar"]').setAttribute('aria-expanded', state.sidebar); return showMode();
            case 'fullscreen': return document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen();
            case 'share': return setupShare();
            case 'copy-link': await navigator.clipboard.writeText($('[data-share-url]').value); return notify('Tautan berhasil disalin.');
            case 'add-bookmark': return addBookmark();
            case 'theme': document.documentElement.dataset.readerTheme = document.documentElement.dataset.readerTheme === 'dark' ? 'light' : 'dark'; return;
            case 'reduced-motion': state.reduced = !state.reduced; root.classList.toggle('reduce-motion', state.reduced); return notify(state.reduced ? 'Animasi dikurangi.' : 'Animasi diaktifkan.');
            case 'more': { const menu = $('[data-more-menu]'); menu.hidden = !menu.hidden; $('[data-action="more"]').setAttribute('aria-expanded', !menu.hidden); return; }
            case 'favorite': {
                if (root.dataset.authenticated !== 'true') return notify('Masuk sebagai anggota untuk menyimpan favorit.');
                const button = $('[data-action="favorite"]'); const active = button.getAttribute('aria-pressed') === 'true';
                try { await api(root.dataset.favoriteUrl, {method: active ? 'DELETE' : 'PUT'}); button.setAttribute('aria-pressed', String(!active)); notify(active ? 'Dihapus dari favorit.' : 'Disimpan ke favorit.'); }
                catch (error) { notify(error.message); } return;
            }
            case 'print': window.open(root.dataset.documentUrl, '_blank', 'noopener'); return notify('Gunakan perintah cetak pada tab dokumen.');
            case 'retry': errorBox.hidden = true; return loadPdf();
        }
    }

    function bindEvents() {
        root.addEventListener('click', event => {
            const control = event.target.closest('[data-action]');
            if (control) { event.preventDefault(); perform(control.dataset.action); }
        });
        pageInput.addEventListener('change', () => goToPage(pageInput.value));
        $$('.reader-tabs [role="tab"]').forEach(tab => tab.addEventListener('click', () => {
            $$('.reader-tabs [role="tab"]').forEach(item => item.setAttribute('aria-selected', String(item === tab)));
            $$('.reader-panel').forEach(panel => panel.classList.toggle('is-active', panel.dataset.panel === tab.dataset.tab));
        }));
        $('[data-search-form]').addEventListener('submit', event => {
            event.preventDefault(); const query = new FormData(event.currentTarget).get('query') || $('#reader-search').value.trim();
            if (query.length >= 2) searchDocument(query);
        });
        $('#reader-search').name = 'query';
        window.addEventListener('keydown', event => {
            if (event.target.matches('input, textarea')) return;
            if (event.key === 'ArrowLeft') perform('previous');
            if (event.key === 'ArrowRight' || event.key === ' ') { event.preventDefault(); perform('next'); }
            if (event.key === '+' || event.key === '=') perform('zoom-in');
            if (event.key === '-') perform('zoom-out');
            if (event.key.toLowerCase() === 'f') perform('fullscreen');
        });
        let touchStart = 0;
        stage.addEventListener('touchstart', event => { touchStart = event.changedTouches[0].clientX; }, {passive: true});
        stage.addEventListener('touchend', event => {
            const distance = event.changedTouches[0].clientX - touchStart;
            if (state.mode === 'flip' && Math.abs(distance) > 55) perform(distance < 0 ? 'next' : 'previous');
        }, {passive: true});
        window.addEventListener('resize', () => { clearTimeout(window.readerResizeTimer); window.readerResizeTimer = setTimeout(() => showMode(), 180); });
        window.addEventListener('beforeunload', () => saveProgress());
    }

    async function loadPdf() {
        loading.hidden = false; errorBox.hidden = true;
        try {
            const pdfjs = await import(/* @vite-ignore */ '/vendor/pdfjs/pdf.min.mjs');
            pdfjs.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.mjs';
            state.pdf = await pdfjs.getDocument({url: root.dataset.documentUrl, withCredentials: true}).promise;
            state.total = state.pdf.numPages;
            state.page = Math.max(1, Math.min(state.page || Number(localStorage.getItem(`reader:last-page:${root.dataset.bookSlug}`)) || 1, state.total));
            if (root.dataset.authenticated !== 'true' && !state.bookmarks.length) state.bookmarks = JSON.parse(localStorage.getItem(`reader:bookmarks:${root.dataset.bookSlug}`) || '[]');
            loading.hidden = true;
            setupThumbnails(); setupOutline(); renderBookmarks(); await showMode(true); saveAnalytics(true);
        } catch (error) { showError(error); }
    }

    bindEvents();
    loadPdf();
}
