# Development Guide

Dokumen ini khusus environment lokal/testing dan tidak boleh dipublikasikan sebagai
halaman production.

## Akun demo

Setelah `php artisan migrate:fresh --seed`, tersedia:

| Akses | Email |
|---|---|
| Superadmin | `superadmin@demo.test` |
| Anggota | `member@demo.test` |

Kata sandi default development: `KpuDemo!2026`. Ubah dengan
`DEMO_ACCOUNT_PASSWORD` sebelum menjalankan seeder. Akun tersebut tidak dibuat ketika
`APP_ENV=production`.

## Data demo yang tersedia

Seeder development bersifat idempoten dan menyiapkan data yang langsung dapat dipakai
untuk menguji portal, dashboard, serta area anggota:

- 10 kategori, 5 rak, dan 20 buku dummy original dengan PDF reader 12 halaman;
- sampul WebP, penulis, penerbit, bahasa, tag, dan relasi katalog;
- statistik kunjungan, 8 unduhan, serta 8 pencarian termasuk pencarian tanpa hasil;
- 3 favorit, 4 riwayat baca, 3 bookmark, 1 koleksi pribadi, dan 2 langganan kategori;
- 2 saran/laporan dokumen, 4 audit log immutable, dan 1 pengumuman.

Untuk mengisi ulang hanya data demo setelah migration sudah tersedia:

```bash
php artisan db:seed --class=DemoAdminSeeder
php artisan db:seed --class=CatalogSeeder
php artisan db:seed --class=DemoActivitySeeder
```

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

## Model akses dan permission override

`public` adalah konteks tanpa akun database. Registrasi hanya menghasilkan role `member`,
sedangkan dashboard hanya dapat digunakan role `superadmin`.

Hak bawaan superadmin berasal dari `role_permissions`. Setelah role superadmin
terverifikasi, baris di `user_permissions` dapat mencabut (`allowed=0`) atau menambahkan
(`allowed=1`) permission untuk akun tersebut. Override milik member tidak pernah
menaikkan privilege. Semua route admin harus memakai middleware `permission:<nama>` dan
operasi resource memakai policy yang mendelegasikan keputusan ke permission service.

Pada upgrade database lama, akun admin berlevel superadmin dipromosikan menjadi role
`superadmin`. Akun editor, content-admin, dan auditor lama dipindahkan menjadi member
inactive agar tidak lagi memiliki akses dashboard, sementara referensi audit tetap utuh.

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

## 2FA superadmin

Buka **Dashboard → Keamanan akun**, tambahkan secret ke aplikasi autentikator TOTP, lalu
masukkan kode enam digit. Kode pemulihan hanya ditampilkan sekali. Pengaturan kewajiban
2FA berlaku untuk akun superadmin; member tidak dapat membuka endpoint setup, challenge,
atau penonaktifan 2FA administrator.
