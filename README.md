# E-Perpustakaan Digital KPU

Sistem katalog dan pembaca dokumen digital berbasis Laravel. Aplikasi dirancang sebagai
portal pemerintahan yang responsif, dapat dipasang sebagai PWA, dan dapat dideploy ke
Linux/cPanel tanpa Docker.

## Status implementasi

Tahap 1–6 telah diimplementasikan dan menyediakan:

- Laravel 13.19, PHP 8.3+, Livewire 4, Tailwind CSS 4, dan Vite 8;
- seluruh migration tabel domain wajib dari database kosong;
- autentikasi login/logout, registrasi setting-controlled, reset password, verifikasi email;
- tepat tiga level akses: `public` sebagai konteks tanpa akun, `member` sebagai akun
  anggota, dan `superadmin` sebagai satu-satunya akun pengelola;
- permission granular melalui `role_permissions`, override `user_permissions` khusus
  superadmin, middleware, policy, dan Gate;
- permission, pengaturan awal, serta satu akun superadmin dan satu akun member demo
  untuk non-production;
- model, relasi, factory, serta seeder demo idempoten untuk katalog dan aktivitas
  (10 kategori, 5 rak, 20 buku, statistik, riwayat anggota, feedback, dan audit log);
- dashboard khusus superadmin, pencarian/pagination buku, serta CRUD kategori dan koleksi;
- unggah PDF privat dengan validasi MIME/signature/struktur, nama UUID, progress upload,
  versioning awal, dan queue pemrosesan metadata/sampul WebP;
- status pemrosesan dan pesan kegagalan yang dapat dipantau dari dashboard;
- portal publik berbasis database dengan beranda, katalog, kategori, rak, detail buku,
  koleksi terbaru/terpopuler, halaman informasi, sitemap, dan robots;
- pencarian metadata lintas-relasi, filter lengkap, tujuh pilihan sorting, pagination,
  tiga mode tampilan, autocomplete JSON, dan empty state;
- akses detail terpusat untuk publik, unlisted, anggota, email terverifikasi, password,
  privat, terjadwal, dan kedaluwarsa tanpa mengekspos path file;
- SEO per buku, canonical, Open Graph, JSON-LD, dark mode, high contrast, reduced motion,
  mobile navigation, dan aset demo original;
- reader PDF.js dengan lazy rendering, flip/scroll, thumbnail, outline, pencarian teks,
  zoom/fit, satu-dua halaman, fullscreen, tema, reduced motion, keyboard, dan swipe;
- pengiriman PDF privat memakai signed URL dan byte range, download/print sesuai izin;
- area anggota: profil, password, favorit, bookmark, riwayat, halaman terakhir, koleksi
  pribadi, langganan kategori, notifikasi buku baru, dan penghapusan akun;
- sharing, QR code, dan embed buku/rak/kategori dengan allowlist domain;
- alur draft → tinjauan → dikembalikan/terbit/terjadwal → arsip beserta catatan yang
  seluruhnya dikelola superadmin;
- statistik privasi-sadar dan ekspor CSV/XLSX/PDF, feedback/laporan, audit log immutable;
- backup database melalui queue/CLI/scheduler dengan ZIP, manifest, checksum, dan status;
- pengelolaan akun member/superadmin, permission override superadmin, registrasi member,
  domain embed, dan 2FA superadmin;
- PWA, service worker, CSP/header keamanan, SEO, aksesibilitas, dan UI responsif;
- automated tests untuk fondasi, autentikasi, permission, katalog, PDF, reader, member,
  workflow, analytics, audit, backup, embed, PWA, 2FA, dan administrasi sistem.

## Persyaratan

- PHP 8.3 atau lebih baru beserta ekstensi umum Laravel dan `pdo_mysql`;
- Composer 2;
- Node.js 20+ dan npm untuk build asset;
- MySQL 8+ atau MariaDB 10.6+ untuk production;
- database queue (default) atau Redis.

## Instalasi lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Atur koneksi database di `.env`, kemudian:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
php artisan queue:work --tries=3
php artisan schedule:work
```

Untuk development terpadu:

```bash
composer dev
```

Detail akun non-production terdapat di [panduan development](docs/DEVELOPMENT.md).

## Quality checks

```bash
php artisan migrate:fresh --seed --force
php artisan test
vendor/bin/pint --test
npm run build
php artisan optimize:clear
php artisan optimize
```

## Dokumentasi

- [Arsitektur, ERD, route map, tabel, risiko, PDF, dan cPanel](docs/ARCHITECTURE.md)
- [Development dan akun demo](docs/DEVELOPMENT.md)
- [Deployment cPanel](docs/CPANEL.md)
- [Checklist pengujian manual](docs/MANUAL_TESTING.md)
- [Progress proyek](vibe/TASKS.md)

## Security baseline

Jangan commit `.env`, database lokal, file PDF privat, credential SMTP, atau backup.
Production wajib HTTPS, `APP_DEBUG=false`, credential unik, dan `APP_ENV=production`.
Seeder tidak membuat akun demo ketika environment bernilai `production`.
