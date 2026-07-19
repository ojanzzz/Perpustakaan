# Codebase — E-PERPUSTAKAAN DIGITAL KPU

> 2026-07-19 · Updated for direct publication and the supplied application logo.

## Runtime

- Laravel 13.19 / PHP 8.3+
- Livewire 4.3 / Tailwind CSS 4 / Vite 8
- MySQL/MariaDB production; SQLite in-memory tests
- Database queue default

## Foundation and content boundaries

- `app/Enums`: authoritative `public`/`member`/`superadmin` role and account status values.
- `app/Domain/Authorization/PermissionService.php`: capability resolution.
- `app/Http/Middleware/RequirePermission.php`: route-level permission enforcement.
- `app/Policies/UserPolicy.php`: target-aware member/superadmin account authorization.
- `app/Http/Controllers/Auth`: login, registration, reset, verification.
- `app/Models/User.php`: role invariant and permission override relation.
- `database/migrations`: identity, authorization, catalog, engagement, governance schema.
- `database/seeders`: permissions, role mappings, settings, and non-production
  superadmin/member demo accounts.
- `tests/Feature/Foundation`: fresh schema and behavior contracts.
- `app/Models/Book.php` and catalog models: metadata and many-to-many taxonomy relations.
- `app/Domain/Documents`: PDF validation and atomic private-file ingestion; every valid
  new upload is assigned `published` plus `published_at` before queue dispatch.
- `app/Jobs/ProcessPdf.php`: queued metadata, checksum, page count, and WebP cover generation.
- `app/Http/Controllers/Admin`: dashboard and permission-protected catalog management.
- `resources/views/admin`: responsive dashboard, book upload/edit, category, and collection UI.
- `resources/views/components/layouts`: public and administrator shells use
  `public/images/logo.png`; the same asset is used by favicon, manifest, and service worker.
- `tests/Feature/Content`: catalog, upload, processing, permission, dashboard, and seed contracts.
- `app/Domain/Catalog/BookAccessService.php`: one publication/access boundary for discovery and direct detail.
- `app/Domain/Search/CatalogSearch.php`: metadata relations, filters, sorting, pagination, and privacy-preserving logs.
- `app/Http/Controllers/PublicPortal`: home, catalog, taxonomy, detail/unlock, autocomplete, sitemap, and robots.
- `resources/views/public` and `components/public`: responsive public portal and reusable discovery UI.
- `components/public/book-cover.blade.php` and `resources/css/app.css`: layered book-card cover with
  fine-pointer/keyboard 3D opening plus static mobile and reduced-motion fallbacks.
- `public/images/demo-covers`: original locally optimized demo artwork; no third-party publication covers.
- `tests/Feature/PublicPortal`: public access, search, route, password, API, and SEO contracts.
- `app/Domain/Documents/DocumentDeliveryService.php`: signed, private, range-aware PDF delivery.
- `app/Http/Controllers/Reader` and `resources/js/reader.js`: reader bootstrap, PDF.js lazy rendering,
  flip/scroll presentation, directional 3D page-sheet animation, member progress/bookmark APIs,
  share, and QR controls.
- `public/vendor/pdfjs` and `public/vendor/qrcode`: locally served reader runtimes with licenses.
- `app/Http/Controllers/Member` and `resources/views/member`: profile, password, favorites, history,
  bookmarks, personal collections, category subscriptions, database notifications, and account deletion.
- `tests/Feature/Reader` and `tests/Feature/Member`: Phase 4 delivery, member state, and account contracts.

## Permission precedence

1. Inactive/suspended or missing user: deny.
2. Any role other than `superadmin`: deny administrative permissions.
3. Explicit `user_permissions` row for a superadmin: return its `allowed` value.
4. Superadmin `role_permissions` mapping: allow when present.
5. Otherwise: deny.

Public access is an unauthenticated request context, not a persisted user. The `users`
table has no `admin_level`; fresh installs use `role_permissions`. During upgrades,
legacy superadmin rows become role `superadmin`, while editor/content-admin/auditor rows
become inactive members so their audit relationships remain intact.

## Current routes

Home, login/logout, setting-controlled registration, password reset, email verification,
superadmin-only dashboard `/admin`, book management `/admin/books`, category management
`/admin/categories`, and collection management `/admin/collections`. Every admin mutation
uses granular permission middleware; resource policies share the same permission service.
Book creation publishes immediately. The former submit/return/publish/archive endpoints
and their controller/domain workflow have been removed; legacy review rows remain readable
at the data layer for audit compatibility.

Public discovery routes include `/`, `/katalog`, `/cari`, `/terbaru`, `/terpopuler`,
`/rak/{slug}`, `/kategori/{slug}`, `/buku/{slug}`, reader and signed document routes,
static information pages, sitemap/robots, and `GET /api/search/suggestions`.
Member routes include profile/password/account deletion, favorites, reading history, bookmarks,
personal collections, category subscriptions, notifications, and authenticated reader APIs.
