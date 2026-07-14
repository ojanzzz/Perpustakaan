# Development Guide

Dokumen ini khusus environment lokal/testing dan tidak boleh dipublikasikan sebagai
halaman production.

## Akun admin demo

Setelah `php artisan migrate:fresh --seed`, tersedia:

| Level | Email |
|---|---|
| Superadmin | `superadmin@demo.test` |
| Admin Konten | `content.admin@demo.test` |
| Editor | `editor@demo.test` |
| Auditor | `auditor@demo.test` |
| Anggota | `member@demo.test` |

Kata sandi default development: `KpuDemo!2026`. Ubah dengan
`DEMO_ACCOUNT_PASSWORD` sebelum menjalankan seeder. Akun tersebut tidak dibuat ketika
`APP_ENV=production`.

## SQLite cepat

Untuk development tanpa MySQL, ubah `.env`:

```dotenv
DB_CONNECTION=sqlite
```

Pastikan `database/database.sqlite` ada, lalu jalankan migration. Test suite menggunakan
SQLite in-memory sehingga tidak menyentuh database development.

## Menjalankan aplikasi

```bash
composer install
npm install
php artisan migrate --seed
composer dev
```

## Permission override

Hak bawaan berasal dari `admin_level_permissions`. Baris di `user_permissions` selalu
menang atas hak level: `allowed=0` mencabut dan `allowed=1` menambahkan permission.
Semua route admin harus memakai middleware `permission:<nama>` dan operasi resource
memakai policy yang mendelegasikan keputusan ke permission service.

## Pemrosesan PDF

File asli disimpan pada disk `private`; jangan membuat symlink publik untuk folder ini.
Jalankan worker agar metadata dan sampul diproses:

```bash
php artisan queue:work --tries=3 --timeout=180
```

`pdfinfo` digunakan untuk jumlah halaman dan `pdftoppm` untuk halaman sampul. Lokasi
binary dapat diatur melalui `PDFINFO_BINARY` dan `PDFTOPPM_BINARY`. Jika binary tidak
tersedia, metadata dasar tetap diproses menggunakan pemeriksaan struktur internal,
sedangkan sampul dapat ditambahkan kembali setelah binary tersedia dan job diulang.

## Reader dan PWA

Reader memakai modul PDF.js lokal di `public/vendor/pdfjs`; tidak ada ketergantungan CDN.
PDF dirender per halaman saat diperlukan. Service worker hanya menyimpan shell publik dan
secara eksplisit tidak meng-cache endpoint dokumen privat. PWA aktif pada asset production
setelah `npm run build`.

## Backup

Jalankan langsung atau melalui queue:

```bash
php artisan library:backup
php artisan library:backup --queue
```

Arsip berada di `storage/app/backups`, tidak boleh disymlink ke publik. Verifikasi checksum
yang tercatat pada dashboard sebelum memindahkan arsip ke media penyimpanan lain.

## 2FA administrator

Buka **Dashboard → Keamanan akun**, tambahkan secret ke aplikasi autentikator TOTP, lalu
masukkan kode enam digit. Kode pemulihan hanya ditampilkan sekali. Superadmin dapat
mewajibkan 2FA seluruh admin melalui menu Pengaturan.
