# Manual Testing Checklist

## Desktop

- [ ] Beranda menampilkan kategori, terbaru, populer, rak, statistik, dan pengumuman dari database.
- [ ] Katalog mencari judul/relasi, menerapkan seluruh filter, sorting, dan pagination.
- [ ] Mode grid, list, dan shelf mempertahankan query filter.
- [ ] Autocomplete hanya menampilkan buku yang dapat ditemukan pengguna.
- [ ] Detail buku tidak pernah menampilkan `original_file` atau path storage privat.
- [ ] Buku unlisted hanya bisa dibuka langsung; private/draft/future/expired menghasilkan 404.
- [ ] Password salah menampilkan validasi; password benar membuka metadata hanya untuk sesi berjalan.
- [ ] Canonical, Open Graph, JSON-LD, sitemap, dan robots tersedia.

- [ ] Halaman `/`, `/login`, `/forgot-password` tampil tanpa error console.
- [ ] Login benar berhasil dan session berganti; login salah menampilkan error generik.
- [ ] Akun inactive ditolak.
- [ ] Registrasi menghasilkan 404 ketika setting nonaktif.
- [ ] Member menerima 403 saat membuka `/admin`, termasuk bila memiliki baris override permission.
- [ ] Superadmin tanpa `dashboard.view` menerima 403; setelah permission diberikan dapat membuka `/admin`.
- [ ] Dashboard menampilkan metrik, grafik tujuh hari, buku terbaru, dan status backup.
- [ ] Superadmin dapat membuat draft, mengunggah PDF valid, mengubah metadata, dan menghapus buku sesuai policy.
- [ ] PDF rusak/non-PDF ditolak dengan pesan yang jelas dan tidak meninggalkan file privat.
- [ ] Status pemrosesan berubah pending → processing → completed atau failed.
- [ ] Superadmin dapat membuat, mengubah, dan menghapus kategori/koleksi kosong.
- [ ] Member tidak dapat membaca dashboard maupun menjalankan mutasi admin.
- [ ] Daftar buku mencari judul, memfilter status, dan mempertahankan query saat pagination.
- [ ] Navigasi keyboard dan focus state terlihat.
- [ ] Reader membuka `?page=10`, lazy-render halaman, serta berpindah flip/scroll tanpa memuat semua halaman.
- [ ] Zoom, fit width/page, thumbnail, outline, pencarian teks, fullscreen, tema, dan reduced motion berfungsi.
- [ ] Download dan print hanya muncul/berhasil jika izin buku aktif.
- [ ] Favorit, bookmark, riwayat, halaman terakhir, koleksi pribadi, langganan, dan notifikasi tersimpan.
- [ ] Superadmin menjalankan alur draft → tinjauan → dikembalikan/diterbitkan/dijadwalkan → arsip beserta catatan.
- [ ] Member tidak dapat melihat statistik admin, audit log, atau status backup.
- [ ] Audit log menolak update/delete langsung dan ekspor CSV dapat dibuka.
- [ ] Backup queued berubah pending → running → completed dan checksum cocok.
- [ ] Embed buku/rak/kategori ditolak dari referer di luar allowlist.
- [ ] Superadmin dengan 2FA aktif selalu melewati challenge setelah login baru; member ditolak dari seluruh endpoint 2FA admin.
- [ ] Manifest PWA terdeteksi; offline shell tersedia; endpoint PDF tidak masuk cache.

## Mobile

- [ ] Tidak ada overflow horizontal pada lebar 320, 375, dan 430 piksel.
- [ ] Input dapat digunakan tanpa zoom/cropping yang menghalangi.
- [ ] Target sentuh tombol utama mudah digunakan.
- [ ] Pesan error terbaca screen reader dan tidak hanya mengandalkan warna.
- [ ] Form unggah, pilihan kategori/koleksi, dan progress PDF dapat digunakan pada 375 piksel.
- [ ] Menu portal, filter katalog, autocomplete, grid buku, dan detail satu kolom berfungsi pada 375 piksel.
- [ ] Dark mode dan kontras tinggi dapat diaktifkan dari menu seluler dan tersimpan lokal.
- [ ] Reader otomatis memakai satu halaman/scroll, drawer tertutup, swipe dan input halaman dapat digunakan.
- [ ] QR/share dialog tidak keluar viewport pada 320, 375, dan 430 piksel.

## Database kosong

- [ ] `php artisan migrate:fresh --seed --force` berhasil.
- [ ] Tabel `users` tidak memiliki `admin_level`; enum role hanya `public`, `member`, dan `superadmin`.
- [ ] Tidak ada akun database ber-role `public`; seeder non-production hanya membuat satu superadmin dan satu member.
- [ ] `role_permissions` memberi hak granular kepada superadmin dan override member tidak dapat menaikkan privilege.
- [ ] Upgrade database mempertahankan member, mempromosikan superadmin lama, dan mengubah editor/content-admin/auditor lama menjadi member inactive.
- [ ] Seeder production tidak membuat akun demo.
