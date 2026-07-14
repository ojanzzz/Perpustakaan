# Reader Page-Turn Animation

## Overview

Reader mode flip menampilkan lembar halaman aktif yang berputar pada sumbu punggung buku, dengan perspektif, bayangan dinamis, dan sisi belakang kertas. Mode scroll tidak berubah.

## Visual direction

Referensi interaksi: `https://codepen.io/roque363/pen/eYOvqBL`. Implementasi hanya mengadaptasi prinsip visual berupa perspektif buku, titik putar di sisi punggung, lapisan kertas hangat, sudut luar membulat, dan bayangan yang menguat saat halaman terangkat. Seluruh struktur reader, aset, warna portal, dan animasi pergantian halaman dibuat mandiri untuk aplikasi ini.

## Acceptance criteria

- Tombol next dan previous menghasilkan lipatan 3D dengan arah yang sesuai.
- Isi halaman tujuan sudah tersedia di belakang lembar yang bergerak sehingga tidak berkedip kosong.
- Mode dua halaman berputar dari sisi kanan saat maju dan sisi kiri saat mundur.
- Navigasi cepat tidak meninggalkan overlay animasi atau menampilkan hasil render lama.
- Reduced-motion menonaktifkan page turn 3D.
- Reader mobile tetap memakai mode scroll otomatis dan tidak mengalami overflow.

## Integration

- `resources/js/reader.js`: lifecycle render dan cleanup overlay.
- `resources/css/reader.css`: perspektif, lembar depan/belakang, bayangan, keyframes.
- `tests/Feature/Reader/ReaderAnimationContractTest.php`: kontrak asset dan aksesibilitas.

Tidak ada perubahan route, API, database, atau delivery PDF.
