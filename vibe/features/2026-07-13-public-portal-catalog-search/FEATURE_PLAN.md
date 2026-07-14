# Phase 3 Implementation Plan

Goal: deliver the complete public discovery portal while keeping document delivery private for Phase 4.

Approved direction: the user-provided Phase 3 requirements and the three concepts in
`docs/design/phase-3`, implemented with original assets and no copied logo/content.

## Task 1 — Public access and query contracts

Objective: centralize publication/access rules and searchable/filterable queries.

Files:
- Modify `app/Models/Book.php`, `app/Models/User.php`
- Create `app/Domain/Catalog/BookAccessService.php`
- Create `app/Domain/Search/CatalogSearch.php`
- Test `tests/Feature/PublicPortal/PublicBookAccessTest.php`

Steps: write failing access/search tests; run targeted tests; implement scopes/services;
rerun until green. No private file response is allowed.

## Task 2 — Public controllers, requests, and routes

Objective: provide home, catalog, taxonomy, detail, password unlock, static, sitemap,
robots, and JSON suggestion endpoints.

Files:
- Create `app/Http/Controllers/PublicPortal/*`
- Create `app/Http/Requests/PublicPortal/*`
- Modify `routes/web.php`, `routes/api.php`
- Test `tests/Feature/PublicPortal/PublicRoutesTest.php`, `CatalogSearchTest.php`

Steps: write route/response tests first; implement thin controllers around domain services;
verify 404/locked/access behavior and JSON bounds.

## Task 3 — Public design system and reusable components

Objective: implement the accepted white/navy/red editorial system in Blade.

Files:
- Modify `resources/css/app.css`, `resources/js/app.js`, public layout
- Create `resources/views/components/public/*`
- Create `resources/views/public/*`
- Create original cover assets under `public/images/demo-covers`

Validation: production build plus Browser/IAB comparison at desktop and 375px.

## Task 4 — Published demo data and SEO

Objective: seed twenty discoverable records, activity counts, announcements, and generic artwork;
render canonical, Open Graph, JSON-LD, sitemap, and robots.

Files:
- Modify `database/seeders/CatalogSeeder.php`
- Modify `tests/Feature/Content/CatalogSeederTest.php`
- Test `tests/Feature/PublicPortal/SeoTest.php`

## Task 5 — Review and verification

Run targeted tests, full test suite, Pint, fresh migration/seed, Vite build, dependency audits,
route inspection, query-count smoke checks, Browser/IAB desktop/mobile workflow, and concept/render
`view_image` comparison. Update README, CODEBASE, TASKS, DECISIONS, and manual checklist.

## Rollback

Remove new public routes/controllers/services/views and restore the prior home closure. No destructive
schema migration is required; seeded publication/activity changes are development data only.

