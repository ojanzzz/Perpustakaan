# Deployment cPanel

## Layout aman

Gunakan dua lokasi:

```text
/home/ACCOUNT/apps/eperpustakaan/    # seluruh project Laravel
/home/ACCOUNT/public_html/library/   # hanya isi folder public Laravel
```

Jangan menaruh `.env`, `vendor`, `storage`, source PHP, atau PDF privat di document root.

## Prosedur

1. Build asset pada development/CI dengan `npm ci && npm run build`.
2. Upload project ke `~/apps/eperpustakaan`, termasuk `vendor` jika SSH Composer tidak ada.
3. Salin isi `public/` ke document root domain/subdomain.
4. Ubah dua path pada `index.php` document root agar menunjuk ke:
   `~/apps/eperpustakaan/vendor/autoload.php` dan
   `~/apps/eperpustakaan/bootstrap/app.php`.
   Contoh ketika document root tidak dapat diarahkan ke folder `public`:

```php
require '/home/ACCOUNT/apps/eperpustakaan/vendor/autoload.php';
$app = require_once '/home/ACCOUNT/apps/eperpustakaan/bootstrap/app.php';
```

   Salin juga `.htaccess`, `build/`, `vendor/pdfjs/`, `vendor/qrcode/`, `images/`,
   `manifest.webmanifest`, dan `service-worker.js` dari `public/`. Jangan menyalin folder
   private project ke `public_html`.
5. Buat `.env` production di project, bukan document root.
6. Pilih PHP 8.3+, aktifkan ekstensi Laravel dan `pdo_mysql`.
7. Jalankan:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=SettingSeeder --force
php artisan optimize
php artisan storage:link
```

Seeder akun demo sengaja dilewati pada `APP_ENV=production`.

## Writable directories

`storage/` dan `bootstrap/cache/` harus dapat ditulis user PHP. Gunakan permission minimum
yang didukung hosting (umumnya direktori 755/775); jangan memakai 777 kecuali sementara
untuk diagnosis dan segera kembalikan.

## Queue dan scheduler

Jika Supervisor tersedia, jalankan worker persisten. Jika tidak, gunakan cron satu menit:

```cron
* * * * * cd /home/ACCOUNT/apps/eperpustakaan && php artisan schedule:run >/dev/null 2>&1
* * * * * cd /home/ACCOUNT/apps/eperpustakaan && php artisan queue:work --stop-when-empty --tries=3 >/dev/null 2>&1
```

Sesuaikan path binary PHP dengan MultiPHP cPanel.

Jika proses worker dibatasi hosting, cron `queue:work --stop-when-empty` di atas aman untuk
job pendek. Naikkan batas waktu cron menjadi minimal 10 menit untuk PDF besar dan backup.
Pastikan `QUEUE_CONNECTION=database`, lalu cek tabel `jobs` dan `failed_jobs`.

## Konfigurasi production

Gunakan `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, `SESSION_SECURE_COOKIE=true`,
password database unik, SMTP tervalidasi, dan `LOG_LEVEL=warning`. Jangan menjalankan
`DemoAdminSeeder` di production. Aktifkan 2FA admin dari dashboard setelah akun produksi dibuat.

## Backup dan pemulihan

```bash
php artisan library:backup
sha256sum storage/app/backups/YYYY/MM/eperpustakaan-*.zip
```

Simpan salinan arsip di luar akun hosting. Superadmin dapat memulihkan arsip format terbaru
melalui Dashboard → Backup; job selalu membuat backup pra-restore dan memverifikasi checksum.
Untuk pemulihan manual, aktifkan maintenance, verifikasi `manifest.json` dan checksum, lalu
impor `database.sql` melalui phpMyAdmin atau CLI MySQL. Setelah impor jalankan `php artisan optimize:clear`,
`php artisan migrate --force`, dan smoke test `/up`. File PDF privat harus dipulihkan dari
backup storage terpisah karena backup database tidak menggandakan PDF besar.

## Update dan rollback

Backup database dan storage privat sebelum update. Upload release ke folder baru, jalankan
`composer install --no-dev --optimize-autoloader`, build/upload assets, maintenance mode,
migration, smoke test `/up`, lalu tukar path release. Rollback memakai release sebelumnya;
migration data hanya dibatalkan setelah dampaknya ditinjau dan backup tersedia.

Urutan rollback release: `php artisan down`, arahkan `index.php` ke release sebelumnya,
pulihkan database hanya jika migration baru tidak backward-compatible, jalankan
`php artisan optimize:clear`, lalu `php artisan up`.
