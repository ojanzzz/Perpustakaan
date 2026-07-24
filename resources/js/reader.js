import { PageFlip } from 'page-flip';

const root = document.querySelector('[data-reader-root]');

if (root) {
    const state = {
        pdf: null,
        page: Number((root.dataset.explicitPage === 'true' ? root.dataset.initialPage : root.dataset.savedPage) || root.dataset.initialPage || 1),
        total: Number(root.dataset.totalPages || 1),
        zoom: 1,
        fit: 'page',
        mode: 'flip',
        spread: !window.matchMedia('(max-width: 767px)').matches,
        spreadPreference: null,
        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        sidebar: false,
        renderedScroll: new Map(),
        pagePromises: new Map(),
        bookmarks: JSON.parse(document.querySelector('#reader-bookmarks-data')?.textContent || '[]'),
        startedAt: Date.now(),
        lastProgressAt: Date.now(),
        lastAnalyticsAt: Date.now(),
        readerSession: sessionStorage.getItem('reader:session') || (crypto.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`),
        pageFlip: null,
    };

    const $ = (selector, scope = root) => scope.querySelector(selector);
    const $$ = (selector, scope = root) => [...scope.querySelectorAll(selector)];
    const loading = $('[data-loading]');
    const errorBox = $('[data-error]');
    const flipViewport = $('[data-flip-viewport]');
    const scrollViewport = $('[data-scroll-viewport]');
    const sidebar = $('#reader-sidebar');
    const stage = $('#reader-stage');
    const pageInput = $('[data-page-input]');
    const zoomRange = $('[data-zoom-range]');
    const toast = $('[data-toast]');
    const controlBar = $('.reader-control-bar');
    const sidebarToggle = $('[data-action="toggle-sidebar"]');
    const moreMenu = $('[data-more-menu]');
    const moreButton = $('[data-action="more"]');
    const shareDialog = $('[data-share-dialog]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let toastTimer;
    let controlsHideTimer;
    let progressTimer;
    let scrollObserver;
    let scrollNavigationUntil = 0;
    let scrollAlignmentTimers = [];
    let flipRenderGeneration = 0;
    let pagePointerStart = null;

    root.classList.toggle('sidebar-closed', !state.sidebar);
    sidebar.inert = !state.sidebar;
    sidebar.setAttribute('aria-hidden', String(!state.sidebar));
    root.classList.toggle('reduce-motion', state.reduced);
    sessionStorage.setItem('reader:session', state.readerSession);

    const icons = {
        bookmark: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-6-4-6 4z"/></svg>',
    };

    function controlsAreBusy() {
        return controlBar.matches(':hover')
            || controlBar.contains(document.activeElement)
            || !moreMenu.hidden
            || Boolean(shareDialog?.open);
    }

    function scheduleControlsHide() {
        clearTimeout(controlsHideTimer);
        controlsHideTimer = setTimeout(() => {
            if (controlsAreBusy()) return scheduleControlsHide();
            root.classList.add('reader-controls-hidden');
        }, 2400);
    }

    function revealControls() {
        root.classList.remove('reader-controls-hidden');
        scheduleControlsHide();
    }

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
        const controlBarHeight = controlBar.getBoundingClientRect().height || (window.matchMedia('(max-width: 767px)').matches ? 40 : 50);
        const verticalPadding = controlBarHeight + 16;
        const compact = window.matchMedia('(max-width: 767px)').matches;
        const width = Math.max(200, stage.clientWidth - (compact ? 40 : 160)) / (spread ? 2 : 1);
        const height = Math.max(260, stage.clientHeight - verticalPadding);
        const fitWidth = (width - (compact ? 4 : 12)) / base.width;
        const fitPage = Math.min(fitWidth, height / base.height);
        return (state.fit === 'width' ? fitWidth : fitPage) * state.zoom;
    }

    async function canvasForPage(number, context = 'page') {
        const page = await getPage(number);
        const scale = context === 'thumbnail' ? 0.2 : availableScale(page, context === 'spread');
        const viewport = page.getViewport({scale: Math.max(0.1, scale)});
        const minimumRatio = context === 'thumbnail' ? 1 : 2;
        const maxRenderPixels = context === 'thumbnail' ? 600_000 : 4_500_000;
        const maxRatio = Math.max(1, Math.sqrt(maxRenderPixels / (viewport.width * viewport.height)));
        const ratio = Math.min(Math.max(window.devicePixelRatio || 1, minimumRatio), 3, maxRatio);
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
        zoomRange.value = Math.round(state.zoom * 100);
        root.classList.toggle('page-zoomed', state.zoom > 1);
        const lastVisiblePage = state.mode === 'flip' && state.spread ? Math.min(state.total, state.page + 1) : state.page;
        const canPrevious = state.page > 1;
        const canNext = lastVisiblePage < state.total;
        $$('[data-action="previous"]').forEach(button => { button.disabled = !canPrevious; });
        $$('[data-action="next"]').forEach(button => { button.disabled = !canNext; });
        const url = new URL(window.location.href);
        url.searchParams.set('page', state.page);
        history.replaceState({}, '', url);
        localStorage.setItem(`reader:last-page:${root.dataset.bookSlug}`, String(state.page));
    }

    async function initFlipBook() {
        const renderGeneration = ++flipRenderGeneration;
        if (state.pageFlip) {
            state.pageFlip.destroy();
            state.pageFlip = null;
        }
        flipViewport.hidden = false;
        scrollViewport.hidden = true;

        const stageWidth = stage.clientWidth;
        const stageHeight = stage.clientHeight;
        const basePage = await getPage(1);
        const scale = availableScale(basePage, state.spread);
        const viewport = basePage.getViewport({scale: Math.max(0.1, scale)});
        const pageWidth = Math.max(200, Math.floor(viewport.width));
        const pageHeight = Math.max(260, Math.floor(viewport.height));

        const bookEl = document.createElement('div');
        bookEl.className = 'flip-book';
        bookEl.style.height = `${pageHeight}px`;
        bookEl.style.setProperty('--flip-paper-width', `${state.spread ? pageWidth * 2 : pageWidth}px`);
        const pageElements = [];

        for (let i = 1; i <= state.total; i++) {
            if (renderGeneration !== flipRenderGeneration) return;
            try {
                const canvas = await canvasForPage(i, state.spread ? 'spread' : 'page');
                if (renderGeneration !== flipRenderGeneration) return;
                const pageElement = document.createElement('div');
                const pageImage = document.createElement('img');
                pageElement.className = 'flip-page';
                pageElement.dataset.page = String(i);
                pageImage.src = canvas.toDataURL('image/png');
                pageImage.alt = `Halaman ${i}`;
                pageImage.decoding = 'async';
                pageImage.draggable = false;
                pageElement.appendChild(pageImage);
                pageElements.push(pageElement);
            } catch {
                if (renderGeneration !== flipRenderGeneration) return;
                const fallback = document.createElement('canvas');
                fallback.width = pageWidth;
                fallback.height = pageHeight;
                const fallbackContext = fallback.getContext('2d', {alpha: false});
                fallbackContext.fillStyle = '#ffffff';
                fallbackContext.fillRect(0, 0, pageWidth, pageHeight);
                fallbackContext.fillStyle = '#64748b';
                fallbackContext.font = '16px sans-serif';
                fallbackContext.textAlign = 'center';
                fallbackContext.fillText(`Halaman ${i} gagal dimuat`, pageWidth / 2, pageHeight / 2);
                const pageElement = document.createElement('div');
                const pageImage = document.createElement('img');
                pageElement.className = 'flip-page';
                pageImage.src = fallback.toDataURL('image/png');
                pageImage.alt = `Halaman ${i} gagal dimuat`;
                pageElement.appendChild(pageImage);
                pageElements.push(pageElement);
            }
        }

        if (renderGeneration !== flipRenderGeneration) return;

        flipViewport.innerHTML = '';
        flipViewport.appendChild(bookEl);

        state.pageFlip = new PageFlip(bookEl, {
            width: pageWidth,
            height: pageHeight,
            size: state.zoom > 1 ? 'fixed' : 'stretch',
            minWidth: 200,
            maxWidth: Math.max(200, stageWidth, pageWidth),
            minHeight: 280,
            maxHeight: Math.max(stageHeight, pageHeight),
            maxShadowOpacity: 0.45,
            showCover: false,
            mobileScrollSupport: false,
            flippingTime: state.reduced ? 1 : 800,
            usePortrait: !state.spread,
            startPage: Math.max(0, Math.min(state.page - 1, state.total - 1)),
            drawShadow: true,
            showPageCorners: false,
            disableFlipByClick: true,
            useMouseEvents: state.zoom <= 1,
        });

        state.flipBookSpread = state.spread;

        state.pageFlip.loadFromHTML(pageElements);
        if (state.zoom > 1) {
            state.pageFlip.getUI().removeHandlers();
        }
        state.pageFlip.on('flip', (e) => {
            if (!state.pageFlip) return;
            state.page = e.data + 1;
            updateChrome();
            scheduleProgress();
        });

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
            flipRenderGeneration += 1;
            state.pageFlip?.destroy();
            state.pageFlip = null;
            state.flipBookSpread = null;
            flipViewport.hidden = true;
            scrollViewport.hidden = false;
            if (!scrollViewport.children.length) setupScrollPages();
            if (scrollToPage) {
                scheduleScrollAlignment(state.page);
            }
            updateChrome();
        } else {
            scrollObserver?.disconnect();
            const needsReinit = !state.pageFlip || state.flipBookSpread !== state.spread;
            if (needsReinit) {
                state.pageFlip?.destroy();
                state.pageFlip = null;
                await initFlipBook();
            } else {
                flipViewport.hidden = false;
                scrollViewport.hidden = true;
                const currentIndex = state.pageFlip.getCurrentPageIndex();
                if (currentIndex + 1 !== state.page) {
                    state.pageFlip.flip(state.page - 1);
                }
            }
        }
    }

    async function goToPage(page, direction = 'none') {
        page = Math.max(1, Math.min(Number(page) || 1, state.total));
        if (state.page === page && direction === 'none') return;
        state.page = page;
        updateChrome();
        if (state.mode === 'scroll') {
            scheduleScrollAlignment(state.page, !state.reduced);
        } else if (state.pageFlip) {
            await state.pageFlip.flip(state.page - 1);
        }
        scheduleProgress();
    }

    function applyZoom(zoom) {
        state.zoom = Math.max(0.5, Math.min(3, zoom));
        updateChrome();
        return state.mode === 'flip' ? initFlipBook() : showMode();
    }

    function togglePageZoom() {
        return applyZoom(state.zoom > 1 ? 1 : 1.75);
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
            row.innerHTML = `<button type="button">${icons.bookmark}<span><strong></strong><small></small></span></button><button type="button" class="bookmark-delete" aria-label="Hapus bookmark">×</button>`;
            const bookmarkLabel = $('strong', row);
            bookmarkLabel.textContent = bookmark.label || `Halaman ${bookmark.page}`;
            $('small', row).textContent = `Halaman ${bookmark.page}`;
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
            case 'previous': return state.page > 1 ? goToPage(state.page - (state.mode === 'flip' && state.spread ? 2 : 1), 'previous') : null;
            case 'next': return state.page + (state.mode === 'flip' && state.spread ? 1 : 0) < state.total ? goToPage(state.page + (state.mode === 'flip' && state.spread ? 2 : 1), 'next') : null;
            case 'zoom-in': return applyZoom(state.zoom + 0.15);
            case 'zoom-out': return applyZoom(state.zoom - 0.15);
            case 'fit-width': return setFitMode('width');
            case 'fit-page': return setFitMode('page');
            case 'toggle-mode': state.mode = state.mode === 'flip' ? 'scroll' : 'flip'; return showMode(true);
            case 'toggle-spread':
                state.spread = !state.spread;
                state.spreadPreference = state.spread;
                state.mode = 'flip';
                state.pageFlip?.destroy();
                state.pageFlip = null;
                return initFlipBook();
            case 'toggle-sidebar': setSidebarOpen(!state.sidebar, {restoreFocus: state.sidebar}); return showMode();
            case 'open-panel': return;
            case 'fullscreen': return document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen();
            case 'share': return setupShare();
            case 'copy-link': await navigator.clipboard.writeText($('[data-share-url]').value); return notify('Tautan berhasil disalin.');
            case 'add-bookmark': return addBookmark();
            case 'theme': document.documentElement.dataset.readerTheme = document.documentElement.dataset.readerTheme === 'dark' ? 'light' : 'dark'; return;
            case 'reduced-motion':
                state.reduced = !state.reduced;
                root.classList.toggle('reduce-motion', state.reduced);
                if (state.mode === 'flip') await initFlipBook();
                return notify(state.reduced ? 'Animasi dikurangi.' : 'Animasi diaktifkan.');
            case 'more': return setMoreMenuOpen(moreMenu.hidden, {focusMenu: moreMenu.hidden});
            case 'favorite': {
                if (root.dataset.authenticated !== 'true') return notify('Masuk sebagai anggota untuk menyimpan favorit.');
                const favoriteButtons = $$('[data-action="favorite"]'); const active = favoriteButtons.some(button => button.getAttribute('aria-pressed') === 'true');
                try { await api(root.dataset.favoriteUrl, {method: active ? 'DELETE' : 'PUT'}); favoriteButtons.forEach(button => button.setAttribute('aria-pressed', String(!active))); notify(active ? 'Dihapus dari favorit.' : 'Disimpan ke favorit.'); }
                catch (error) { notify(error.message); } return;
            }
            case 'print': window.open(root.dataset.documentUrl, '_blank', 'noopener'); return notify('Gunakan perintah cetak pada tab dokumen.');
            case 'retry': errorBox.hidden = true; return loadPdf();
        }
    }

    function bindEvents() {
        root.addEventListener('pointermove', revealControls, {passive: true});
        root.addEventListener('pointerdown', revealControls, {passive: true});
        controlBar.addEventListener('pointerenter', () => clearTimeout(controlsHideTimer));
        controlBar.addEventListener('pointerleave', scheduleControlsHide);
        controlBar.addEventListener('focusin', () => clearTimeout(controlsHideTimer));
        controlBar.addEventListener('focusout', scheduleControlsHide);
        shareDialog?.addEventListener('close', scheduleControlsHide);
        document.addEventListener('fullscreenchange', revealControls);
        root.addEventListener('click', event => {
            const control = event.target.closest('[data-action]');
            if (control) {
                event.preventDefault();
                if (control.dataset.panelTrigger) openPanel(control.dataset.panelTrigger);
                if (control.dataset.action !== 'more' && !moreMenu.hidden) {
                    setMoreMenuOpen(false, {restoreFocus: control.dataset.action !== 'share'});
                }
                perform(control.dataset.action);
            }
        });
        pageInput.addEventListener('change', () => goToPage(pageInput.value));
        pageInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                goToPage(pageInput.value);
                pageInput.select();
            }
        });
        zoomRange.addEventListener('input', () => {
            state.zoom = Number(zoomRange.value) / 100;
            $('[data-zoom-label]').textContent = `${zoomRange.value}%`;
            root.classList.toggle('page-zoomed', state.zoom > 1);
            clearTimeout(window.readerZoomTimer);
            window.readerZoomTimer = setTimeout(() => state.mode === 'flip' ? initFlipBook() : showMode(), 90);
        });
        flipViewport.addEventListener('pointerdown', event => {
            if (!event.target.closest('.flip-page')) return;
            pagePointerStart = {x: event.clientX, y: event.clientY};
        }, {passive: true});
        flipViewport.addEventListener('pointerup', event => {
            if (!pagePointerStart || !event.target.closest('.flip-page')) {
                pagePointerStart = null;
                return;
            }
            const distance = Math.hypot(event.clientX - pagePointerStart.x, event.clientY - pagePointerStart.y);
            pagePointerStart = null;
            if (distance <= 8) togglePageZoom();
        }, {passive: true});
        flipViewport.addEventListener('pointercancel', () => { pagePointerStart = null; }, {passive: true});
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
            revealControls();
            if (event.key === 'Escape' && !moreMenu.hidden) {
                event.preventDefault();
                setMoreMenuOpen(false, {restoreFocus: true});
                return;
            }
            if (event.key === 'Escape' && state.sidebar) {
                event.preventDefault();
                setSidebarOpen(false, {restoreFocus: true});
                return;
            }
            if (event.target.matches('input, textarea')) return;
            if (event.key === 'ArrowLeft') perform('previous');
            if (event.key === 'ArrowRight' || event.key === ' ') { event.preventDefault(); perform('next'); }
            if (event.key === '+' || event.key === '=') perform('zoom-in');
            if (event.key === '-') perform('zoom-out');
            if (event.key.toLowerCase() === 'f') perform('fullscreen');
        });
        window.addEventListener('resize', () => {
            clearTimeout(window.readerResizeTimer);
            window.readerResizeTimer = setTimeout(() => {
                if (!state.pdf) return;
                const compact = window.matchMedia('(max-width: 767px)').matches;
                const tablet = window.matchMedia('(min-width: 768px) and (max-width: 1024px)').matches;
                state.spread = compact ? false : (tablet ? state.spreadPreference ?? false : state.spreadPreference ?? true);
                if (compact) state.mode = 'flip';
                if (state.mode === 'flip') {
                    state.pageFlip?.destroy();
                    state.pageFlip = null;
                }
                showMode(true);
            }, 180);
        });
        window.addEventListener('beforeunload', () => saveProgress());
        moreMenu.addEventListener('keydown', event => {
            if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
            const items = $$('[role="menuitem"]:not([disabled])', moreMenu);
            if (!items.length) return;
            event.preventDefault();
            const current = Math.max(0, items.indexOf(document.activeElement));
            const target = event.key === 'Home'
                ? items[0]
                : event.key === 'End'
                    ? items.at(-1)
                    : items[(current + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length];
            target.focus();
        });
        moreMenu.addEventListener('focusout', () => {
            requestAnimationFrame(() => {
                if (!moreMenu.hidden && !moreMenu.contains(document.activeElement) && document.activeElement !== moreButton) {
                    setMoreMenuOpen(false);
                }
            });
        });
        document.addEventListener('click', event => {
            if (!moreMenu.hidden && !moreMenu.contains(event.target) && !moreButton.contains(event.target)) setMoreMenuOpen(false);
        });
    }

    function openPanel(panelName) {
        setSidebarOpen(true);
        $$('.reader-tabs [role="tab"]').forEach(item => item.setAttribute('aria-selected', String(item.dataset.tab === panelName)));
        $$('.reader-panel').forEach(panel => panel.classList.toggle('is-active', panel.dataset.panel === panelName));
        if (panelName === 'search') requestAnimationFrame(() => $('#reader-search')?.focus());
        showMode();
    }

    function setSidebarOpen(open, {restoreFocus = false} = {}) {
        state.sidebar = open;
        root.classList.toggle('sidebar-closed', !open);
        sidebar.inert = !open;
        sidebar.setAttribute('aria-hidden', String(!open));
        sidebarToggle.setAttribute('aria-expanded', String(open));
        if (!open && restoreFocus) sidebarToggle.focus();
    }

    function setFitMode(mode) {
        state.fit = mode;
        state.zoom = 1;
        root.classList.toggle('fit-width-active', mode === 'width');
        return state.mode === 'flip' ? initFlipBook() : showMode();
    }

    function setMoreMenuOpen(open, {restoreFocus = false, focusMenu = false} = {}) {
        moreMenu.hidden = !open;
        moreButton.setAttribute('aria-expanded', String(open));
        if (open) revealControls();
        else scheduleControlsHide();
        if (open && focusMenu) requestAnimationFrame(() => $('[role="menuitem"]', moreMenu)?.focus());
        if (!open && restoreFocus) moreButton.focus();
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
            renderBookmarks(); await showMode(true); saveAnalytics(true);
        } catch (error) { showError(error); }
    }

    bindEvents();
    revealControls();
    loadPdf();
}
