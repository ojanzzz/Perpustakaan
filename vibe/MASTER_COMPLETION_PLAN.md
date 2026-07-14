# Tahap 4-6 Implementation Plan

Goal: Menyelesaikan reader digital, area anggota, governance/operasional, hardening, pengujian, dan dokumentasi deployment sampai aplikasi siap dijalankan secara lokal maupun di cPanel.

Approved direction: Lanjutkan implementasi Tahap 4, Tahap 5, dan Tahap 6 secara berurutan dengan arsitektur modular Laravel yang telah ditetapkan.

Acceptance criteria:
- PDF hanya dikirim setelah otorisasi, melalui URL sementara, mendukung range request, dan tidak membocorkan lokasi file.
- Reader PDF.js menyediakan flip/scroll, lazy rendering, navigasi, zoom, thumbnail, pencarian, fullscreen, tema, bookmark/progress, share, QR, download/print sesuai izin, keyboard, swipe, serta fallback mobile.
- Anggota dapat mengelola profil, password, favorit, bookmark, riwayat, dan penghapusan akun.
- Statistik, audit immutable, feedback/report, backup, ekspor, embed allowlist, PWA, security headers, dan 2FA admin tersedia sesuai permission.
- Migrasi database kosong, seed demo, seluruh automated test, lint, asset build, dan smoke/browser test lulus.
- README, panduan development/manual QA/cPanel, environment example, scheduler, queue, backup, update, dan rollback terdokumentasi.

## Project context

Patterns:
- Form Request di batas HTTP dan domain service untuk workflow.
- `BookAccessService` menjadi satu-satunya sumber keputusan akses buku.
- Route admin memakai middleware permission granular; policy tidak memeriksa admin level secara langsung.
- PDF asli berada di disk `private`; browser menerima signed delivery URL berumur pendek.
- Blade server-rendered dengan JavaScript progresif dan REST endpoint untuk reader.

## Task P4-001 - Data anggota dan kontrak akses reader

Files: migration engagement extension, `app/Models`, `app/Domain/Reader`, `tests/Feature/Reader`.

Steps:
1. Tulis test gagal untuk relasi favorit, progress, bookmark, dan akses reader.
2. Tambahkan model/relasi, validator, dan access/session service minimal.
3. Jalankan targeted test, lalu suite Foundation/PublicPortal.

## Task P4-002 - Secure PDF delivery

Files: `app/Http/Controllers/Reader`, `app/Domain/Documents`, `routes/web.php`, `tests/Feature/Reader/DocumentDeliveryTest.php`.

Steps:
1. Tulis test gagal untuk signed URL, unauthorized/private access, byte range, download, dan print permission.
2. Implementasikan delivery service/controller yang aman dan audit-friendly.
3. Jalankan targeted test dan verifikasi header/range secara HTTP.

## Task P4-003 - Reader UI dan aset demo

Files: `resources/views/reader`, `resources/js/reader.js`, `resources/css/reader.css`, `vite.config.js`, seeder/asset demo, tests reader.

Steps:
1. Tambahkan dependency PDF.js, page-flip, dan QR.
2. Bangun reader responsif berdasarkan design system: lazy page window, flip/scroll, toolbar lengkap, drawer thumbnail/outline/search/bookmark, error/empty/loading states.
3. Hubungkan URL `?page=`, local progress visitor, server progress member, share/QR/download/print.
4. Jalankan build, automated reader tests, dan browser QA desktop/mobile.

## Task P4-004 - Area anggota

Files: `app/Http/Controllers/Member`, Form Requests, `resources/views/member`, routes, tests.

Steps:
1. Tulis test gagal untuk profil, password, favorit, bookmark, riwayat, dan delete account.
2. Implementasikan controller/views serta navigasi.
3. Jalankan targeted tests dan browser QA.

## Task P5-001 - Analytics dan ekspor

Files: `app/Domain/Analytics`, API/controller admin, models/views/tests.

Steps:
1. Tulis test privacy/dedup/progress/download/agregasi/CSV-XLSX-PDF.
2. Implementasikan recorder, dashboard Chart.js, filter tanggal, dan ekspor.
3. Verifikasi query agregasi, response, dan UI.

## Task P5-002 - Feedback dan audit immutable

Files: `app/Domain/Audit`, middleware/listeners/controllers/views/tests.

Steps:
1. Tulis test feedback/report, event audit, read-only auditor, dan penolakan mutasi log.
2. Implementasikan audit recorder/listeners serta dashboard feedback/log.
3. Jalankan targeted dan authorization regression tests.

## Task P5-003 - Backup

Files: `app/Domain/Backup`, job/command/controller/views/tests.

Steps:
1. Tulis test permission, lifecycle, checksum, failure, download, dan cleanup.
2. Implementasikan backup database/file metadata yang cPanel-friendly dan queued.
3. Verifikasi command, scheduler, dashboard, dan status.

## Task P6-001 - Embed, PWA, dan security hardening

Files: middleware/config/routes/views, manifest/service worker, 2FA module/tests.

Steps:
1. Tulis test CSP/security headers, signed/private access, embed allowlist, rate limit, dan 2FA admin.
2. Implementasikan middleware, TOTP/recovery, embed routes, PWA/offline shell, dan maintenance settings.
3. Jalankan security-focused suite dan browser accessibility checks.

## Task P6-002 - Test matrix, performance, dan dokumentasi

Files: all test suites, README, `.env.example`, docs, CI helper scripts.

Steps:
1. Lengkapi test requirement yang belum tercakup dan checklist desktop/mobile.
2. Tambahkan cache/index/eager-loading checks serta asset/PDF lazy-load validation.
3. Dokumentasikan instalasi lokal/Linux/cPanel, queue/cron, backup/restore, update/rollback, demo account.
4. Jalankan fresh migrate+seed, full tests, Pint, build, audits, route/config/view cache, queue smoke, dan browser QA.

## Rollback

- Semua perubahan schema bersifat additive dan mempunyai `down()`.
- Aset frontend dapat dikembalikan dengan build manifest sebelumnya.
- Feature flags/settings menonaktifkan registration, embed, PWA notification, dan maintenance tanpa menghapus data.
- Backup restore hanya dieksekusi superadmin dan selalu membuat pre-restore backup.

## Final review and verification

- Review independen terhadap diff dan requirement coverage.
- Fresh-database verification dari nol.
- Full automated suite, lint/build/audit, dan browser QA desktop/mobile.
- Tidak ada fitur utama bertanda TODO atau tombol nonfungsional.
