# E-PERPUSTAKAAN DIGITAL KPU — Product Brief

## Purpose

Portal katalog dan pembaca dokumen digital KPU yang menyatukan publikasi kepemiluan,
memudahkan pencarian, menyediakan pengalaman baca PDF/flipbook, dan memberi pengelola
alur unggah yang langsung menerbitkan buku serta tetap dapat diaudit.

## Users and access levels

- Public: konteks pengunjung tanpa akun untuk mengakses konten publik.
- Member: akun anggota untuk menyimpan favorit, bookmark, dan riwayat baca.
- Superadmin: satu-satunya akun pengelola dengan akses dashboard dan permission granular.

## Locked stack

- Backend: Laravel on PHP 8.3+, MySQL/MariaDB, database queue by default.
- Frontend: Blade, Livewire, Alpine.js, Tailwind CSS, Vite.
- Reader: PDF.js plus an HTML5 page-flip component and scroll fallback.
- Supporting: Chart.js, QR generator, PWA manifest/service worker.
- Production: Linux/cPanel compatible; Docker is never required.

## Design direction

Original clean government portal: true white surfaces, KPU-inspired red primary,
dark navy information hierarchy, pale gray secondary bands, restrained gold accents,
moderate radii, soft elevation, clear typography, and generous whitespace. No wooden
bookcase visual treatment and no copied assets from the reference site.

## Scope source

`Requitment.txt` and the attached detailed specification supplied on 2026-07-13.
Where they differ, the attached specification and the user's latest message win.

## Scope changes

- 2026-07-14: model visitor/member/admin beserta empat admin level diganti menjadi
  tepat tiga level akses `public`, `member`, dan `superadmin` untuk menyederhanakan
  pengelolaan sistem.
- 2026-07-19: workflow review/publish/archive manual dihapus. Buku baru langsung
  diterbitkan saat berhasil disimpan; data dan riwayat buku lama tetap dipertahankan.
