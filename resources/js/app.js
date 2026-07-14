import Chart from 'chart.js/auto';

if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'));
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.documentElement;
    const savedTheme = localStorage.getItem('portal-theme');
    root.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light';
    const themeButtons = document.querySelectorAll('[data-theme-toggle]');
    const syncThemeLabel = () => themeButtons.forEach((button) => {
        const dark = root.dataset.theme === 'dark';
        button.setAttribute('aria-label', dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
        if (button.textContent.trim()) button.textContent = dark ? 'Mode terang' : 'Mode gelap';
    });
    syncThemeLabel();
    themeButtons.forEach((button) => button.addEventListener('click', () => {
            root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('portal-theme', root.dataset.theme);
            syncThemeLabel();
        }));
    if (localStorage.getItem('portal-contrast') === 'high') root.dataset.contrast = 'high';
    document.querySelectorAll('[data-contrast-toggle]').forEach((button) => button.addEventListener('click', () => {
            const enabled = root.dataset.contrast !== 'high';
            if (enabled) root.dataset.contrast = 'high'; else delete root.dataset.contrast;
            localStorage.setItem('portal-contrast', enabled ? 'high' : 'normal');
        }));

    const menuButton = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('#mobile-menu');
    menuButton?.addEventListener('click', () => {
        const open = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(open));
        menu.hidden = !open;
    });

    const filterButton = document.querySelector('[data-filter-toggle]');
    const filterPanel = document.querySelector('[data-filter-panel]');
    filterButton?.addEventListener('click', () => {
        const open = filterButton.getAttribute('aria-expanded') !== 'true';
        filterButton.setAttribute('aria-expanded', String(open));
        filterPanel.dataset.open = String(open);
    });

    document.querySelectorAll('[data-auto-submit] select').forEach((select) => {
        select.addEventListener('change', () => select.form.submit());
    });

    document.querySelectorAll('[data-autocomplete-form]').forEach((form) => {
        const input = form.querySelector('[data-autocomplete-input]');
        const panel = form.querySelector('[data-autocomplete-panel]');
        if (!input || !panel) return;
        let timer;
        let controller;
        const close = () => { panel.hidden = true; panel.replaceChildren(); };
        input.addEventListener('input', () => {
            clearTimeout(timer);
            controller?.abort();
            const query = input.value.trim();
            if (query.length < 2) return close();
            timer = setTimeout(async () => {
                controller = new AbortController();
                try {
                    const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
                    if (!response.ok) return close();
                    const { data } = await response.json();
                    panel.replaceChildren();
                    data.forEach((item) => {
                        const link = document.createElement('a');
                        link.href = item.url;
                        const title = document.createElement('strong');
                        title.textContent = item.title;
                        const byline = document.createElement('small');
                        byline.textContent = item.byline || 'Publikasi digital';
                        link.append(title, byline);
                        panel.append(link);
                    });
                    panel.hidden = data.length === 0;
                } catch (error) {
                    if (error.name !== 'AbortError') close();
                }
            }, 220);
        });
        input.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
        document.addEventListener('click', (event) => { if (!form.contains(event.target)) close(); });
    });

    const chart = document.querySelector('[data-visit-chart]');
    if (chart) {
        new Chart(chart, {
            type: 'line',
            data: {
                labels: JSON.parse(chart.dataset.labels),
                datasets: [{ label: 'Kunjungan', data: JSON.parse(chart.dataset.values), borderColor: '#b91c1c', backgroundColor: 'rgba(185,28,28,.08)', fill: true, tension: 0.35 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    }
    const statChart = document.querySelector('[data-stat-chart]');
    if (statChart) new Chart(statChart, { type: 'line', data: { labels: JSON.parse(statChart.dataset.labels), datasets: [{ label: 'Dibaca', data: JSON.parse(statChart.dataset.values), borderColor: '#b91c1c', backgroundColor: 'rgba(185,28,28,.08)', fill: true, tension: .32 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    const deviceChart = document.querySelector('[data-device-chart]');
    if (deviceChart) new Chart(deviceChart, { type: 'doughnut', data: { labels: JSON.parse(deviceChart.dataset.labels), datasets: [{ data: JSON.parse(deviceChart.dataset.values), backgroundColor: ['#b91c1c','#0f2747','#c59a3d','#64748b'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
    document.querySelectorAll('[data-upload-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.XMLHttpRequest || !form.querySelector('input[type="file"]')?.files.length) return;
            event.preventDefault();
            const status = form.querySelector('[data-upload-status]');
            const progress = form.querySelector('[data-upload-progress]');
            const percent = form.querySelector('[data-upload-percent]');
            const button = form.querySelector('button[type="submit"], button:not([type])');
            status.hidden = false;
            button.disabled = true;
            const xhr = new XMLHttpRequest();
            xhr.open(form.method || 'POST', form.action);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', (upload) => {
                if (!upload.lengthComputable) return;
                const value = Math.round((upload.loaded / upload.total) * 100);
                progress.value = value;
                percent.textContent = `${value}%`;
            });
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 400) window.location.assign('/admin/books');
                else {
                    button.disabled = false;
                    let message = 'Upload gagal. Periksa data dan coba lagi.';
                    try { message = Object.values(JSON.parse(xhr.responseText).errors || {}).flat().join(' '); } catch (_) {}
                    window.alert(message);
                }
            });
            xhr.addEventListener('error', () => { button.disabled = false; window.alert('Koneksi terputus saat upload.'); });
            xhr.send(new FormData(form));
        });
    });
});
