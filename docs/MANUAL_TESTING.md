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
- [ ] Admin tanpa `dashboard.view` menerima 403.
- [ ] Admin dengan `dashboard.view` dapat membuka `/admin`.
- [ ] Dashboard menampilkan metrik, grafik tujuh hari, buku terbaru, dan status backup.
- [ ] Editor dapat membuat draft dan mengunggah PDF valid tetapi tidak dapat menghapus buku.
- [ ] PDF rusak/non-PDF ditolak dengan pesan yang jelas dan tidak meninggalkan file privat.
- [ ] Status pemrosesan berubah pending → processing → completed atau failed.
- [ ] Admin Konten dapat membuat, mengubah, dan menghapus kategori/koleksi kosong.
- [ ] Auditor dapat membaca dashboard/katalog tetapi seluruh operasi perubahan ditolak.
- [ ] Daftar buku mencari judul, memfilter status, dan mempertahankan query saat pagination.
- [ ] Navigasi keyboard dan focus state terlihat.
- [ ] Reader membuka `?page=10`, lazy-render halaman, serta berpindah flip/scroll tanpa memuat semua halaman.
- [ ] Zoom, fit width/page, thumbnail, outline, pencarian teks, fullscreen, tema, dan reduced motion berfungsi.
- [ ] Download dan print hanya muncul/berhasil jika izin buku aktif.
- [ ] Favorit, bookmark, riwayat, halaman terakhir, koleksi pribadi, langganan, dan notifikasi tersimpan.
- [ ] Editor mengirim draft; Admin Konten mengembalikan dengan catatan atau menerbitkan/menjadwalkan.
- [ ] Auditor hanya dapat melihat statistik, audit, dan status backup tanpa mutasi.
- [ ] Audit log menolak update/delete langsung dan ekspor CSV dapat dibuka.
- [ ] Backup queued berubah pending → running → completed dan checksum cocok.
- [ ] Embed buku/rak/kategori ditolak dari referer di luar allowlist.
- [ ] Admin dengan 2FA aktif selalu melewati challenge setelah login baru.
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
- [ ] Empat level admin hanya tersedia pada non-production.
- [ ] Editor tidak memiliki `books.publish`.
- [ ] Auditor tidak memiliki operasi perubahan konten.
