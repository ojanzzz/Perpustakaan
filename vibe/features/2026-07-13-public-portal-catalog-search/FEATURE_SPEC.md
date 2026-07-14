# Feature Spec — Public Portal, Catalog, and Search

## Overview

Phase 3 exposes the published catalog through an SEO-friendly Blade portal. Visitors can
discover books through home-page rails, metadata search, filters, categories, collections,
latest/popular views, and a permission-aware detail page without exposing private files.

## User stories

- A visitor searches and filters published public books, then opens a detailed record.
- A visitor browses a category or collection using grid, list, or digital-shelf layouts.
- A member can discover records whose visibility requires an authenticated or verified user.
- A visitor can unlock a password-protected record without learning its storage path.
- Search autocomplete returns useful titles and taxonomy terms without loading the catalog page.

## Acceptance criteria

1. Home shows hero search, active categories, latest books, popular books, collections,
   public statistics, and an active announcement using database data.
2. Catalog searches title, subtitle, description, authors, publisher, ISBN, document number,
   categories, collections, tags, and publication year.
3. Catalog filters category, collection, author, publisher, year, language, publication type,
   and visibility; all filter inputs are validated and pagination preserves the query.
4. Catalog sorting supports custom order, newest, oldest, title A-Z/Z-A, most read, and most downloaded.
5. Catalog supports grid, list, and shelf modes with a safe allowlist and responsive output.
6. Category and collection pages show only active taxonomy and accessible published books.
7. Detail exposes complete metadata, access state, related books, canonical/Open Graph metadata,
   structured data, breadcrumbs, and only actions permitted for that record.
8. Public/unlisted/member/verified/password/private/scheduled/expired access combinations are
   resolved by one access service; hidden records return 404 and locked records never expose a file path.
9. Password unlock is rate-limited, validated with the stored password hash, and stored only in session.
10. Search suggestions return bounded JSON results for queries of at least two characters.
11. Search terms are logged without raw IP address or unnecessary visitor identity.
12. Latest, popular, about, guide, contact, privacy, sitemap, and robots routes work.
13. Published demo data provides 20 original metadata records and locally generated generic cover artwork.
14. Empty states, keyboard focus, mobile navigation, reduced motion, dark mode, and high contrast are supported.
15. Public list queries paginate, eager-load displayed relationships, and do not load PDF files.

## Scope boundaries

Included: server-rendered portal, metadata search, autocomplete API, access-state presentation,
password record unlock, SEO metadata, sitemap/robots, and public static information pages.

Deferred: PDF streaming/reader, favorites/history/bookmarks, downloads, sharing/QR/embed, OCR/full-text
PDF search, analytics aggregation, feedback submission, and PWA installation are later phases.

## Integration points

- Routes: `routes/web.php`, `routes/api.php`
- Models/tables: books, categories, collections, authors, publishers, languages, tags,
  book_views, book_downloads, search_logs, announcements, users
- New domain boundaries: `app/Domain/Catalog`, `app/Domain/Search`
- New UI: `resources/views/public`, reusable `resources/views/components/public`
- Design concepts: `docs/design/phase-3/*.png`

## Edge cases

- Blank/one-character queries do not generate autocomplete scans.
- Invalid filter IDs, year ranges, sort, and mode receive validation errors or safe defaults.
- Unlisted books are reachable directly but excluded from searchable catalog pages.
- Password/private records do not leak existence through catalog or suggestion endpoints.
- Expired or future-scheduled records are unavailable even when a slug is known.
- Missing cover images render an original deterministic fallback, not a broken image.

## Non-functional requirements

- Laravel pagination, constrained eager loading, indexed predicates, and short-lived query caching.
- Semantic Blade output, visible focus, 44px primary touch targets, and mobile-first layouts.
- Original design: true white, deep navy, election red, cool gray, restrained gold; no wooden shelf.
- No public PDF URL is introduced in this phase.

