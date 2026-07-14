# Book Card Cover Animation

## Overview

Sampul pada kartu buku publik tampil sebagai buku berlapis dan membuka secara 3D ketika pointer hover atau tautan mendapat fokus keyboard. Arah visual mengadaptasi prinsip interaksi dari `https://codepen.io/roque363/pen/eYOvqBL`, sementara aset, markup, warna, ukuran, dan karakter geraknya tetap mengikuti desain original E-Perpustakaan KPU.

## User stories

- Pengunjung mendapat petunjuk interaktif yang jelas bahwa sampul dapat dibuka untuk melihat detail buku.
- Pengguna keyboard memperoleh respons visual yang setara dengan pengguna pointer.
- Pengguna mobile dan reduced-motion tetap mendapat kartu stabil tanpa gerakan yang mengganggu.

## Acceptance criteria

- Kartu buku memiliki blok halaman dekoratif di belakang cover.
- Hover dan `:focus-visible` memutar cover pada sumbu kiri sekitar `-40deg` dan mengangkat buku secara halus dengan perspektif terskalakan agar tidak menimpa metadata.
- Seluruh area kartu tetap menjadi satu tautan menuju detail buku.
- Cover gambar maupun fallback memakai struktur dan animasi yang sama.
- Efek hanya berlaku pada `.book-card`; cover detail, daftar, mini-cover, dan riwayat tidak ikut membuka.
- Perangkat tanpa hover menampilkan sampul statis.
- `prefers-reduced-motion: reduce` menonaktifkan transformasi 3D.
- Dark mode tetap memiliki kontras blok halaman yang layak.

## Scope

- Modifikasi `resources/views/components/public/book-cover.blade.php`.
- Modifikasi `resources/css/app.css`.
- Kontrak otomatis pada `tests/Feature/PublicPortal/BookCardAnimationContractTest.php`.
- Tidak ada perubahan route, API, model, database, atau aset sampul.

## Edge cases

- Judul panjang dan fallback cover tidak mengubah dimensi kartu.
- Hover tidak boleh memotong sampul atau membuat horizontal overflow pada grid.
- Embed dan halaman lain yang memakai `book-card` mendapatkan perilaku yang sama.

## Conformance

- [x] Struktur lapisan buku dirender.
- [x] Hover dan fokus keyboard setara.
- [x] Pointer kasar dan reduced-motion aman.
- [x] Build, test, serta QA desktop/mobile lulus.
