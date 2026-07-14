# Plan — Book Card Cover Animation

## Impact map

- Modify `resources/views/components/public/book-cover.blade.php`: tambah face dan page block dekoratif.
- Modify `resources/css/app.css`: book perspective, cover hinge, shadow, responsive and accessibility safeguards.
- Add `tests/Feature/PublicPortal/BookCardAnimationContractTest.php`: kontrak markup dan CSS.

## Out of scope

- Reader page-turn, halaman detail, file sampul, controller, route, dan database.

## Execution

1. Tambahkan failing contract test untuk struktur, hover/focus, coarse pointer, dan reduced motion.
2. Implementasikan markup lapisan sampul reusable.
3. Implementasikan animasi CSS yang hanya aktif pada kartu.
4. Jalankan targeted test, public portal suite, build, lalu browser QA desktop/mobile.

## Rollback

Hapus wrapper `book-cover-face` dan `book-cover-pages`, lalu kembalikan aturan `.book-cover` statis sebelumnya.
