# Implementation Plan

1. Tambahkan test kontrak yang gagal untuk hook overlay, animasi dua arah, dan reduced-motion.
2. Ubah `renderFlip()` agar halaman baru dirender di belakang lembar lama, memakai token untuk menolak render basi.
3. Tambahkan overlay page-turn 3D dan cleanup `animationend` dengan fallback timer.
4. Tambahkan CSS sisi depan/belakang kertas, bayangan lipatan, dan keyframes maju/mundur.
5. Jalankan targeted test, full reader suite, Pint, Vite build, lalu browser QA desktop dan mobile.

Rollback: kembalikan `renderFlip()` dan hapus kelas `.page-turn-*`; PDF rendering dan mode scroll tetap independen.
