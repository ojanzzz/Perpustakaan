# Decisions — E-PERPUSTAKAAN DIGITAL KPU

Append-only architecture decision log.

## D-001 — Authoritative role model

- Date: 2026-07-13
- Type: tech-choice
- Decision: use fixed `role` values visitor/member/admin and nullable `admin_level`
  values editor/content_admin/auditor/superadmin. Resolve capabilities through permissions.
- Reason: this is the user's latest explicit requirement and supersedes generic role pivots.

## D-002 — cPanel-oriented application shape

- Date: 2026-07-13
- Type: tech-choice
- Decision: modular Laravel monolith, server-rendered Blade/Livewire, database queue default.
- Reason: reduces runtime services while preserving an upgrade path to Redis/object storage.

## D-003 — PDF processing adapters

- Date: 2026-07-13
- Type: tech-choice
- Decision: use configurable `pdfinfo`/`pdftoppm` binaries for validation, metadata, and cover generation; optimizer is optional and originals remain immutable.
- Reason: local tools exist while shared hosting capabilities vary. Processing failures must be visible without data loss.

## D-004 — Public visibility query boundary

- Date: 2026-07-13
- Type: architecture
- Decision: catalog discovery and direct-detail access use separate query paths backed by one access service;
  unlisted/password records never appear in discovery, while direct access applies session/auth rules.
- Reason: prevents metadata/file leakage and keeps Phase 4 document delivery consistent with Phase 3.

## D-005 — Reader delivery and progressive rendering

- Date: 2026-07-13
- Type: architecture
- Decision: authorize every document request through `BookAccessService`, expose only expiring signed routes,
  support HTTP range delivery, and render pages progressively with PDF.js plus page-flip/scroll presentation modes.
- Reason: keeps private storage authoritative while supporting large PDFs and shared-hosting deployments.

## D-006 — Remaining-stage execution gates

- Date: 2026-07-13
- Type: process
- Decision: complete Phase 4, 5, and 6 sequentially using behavior tests, targeted verification,
  regression suites, and a final fresh-database/browser gate.
- Reason: the remaining features share access, engagement, and governance data and must remain runnable after each stage.

## D-007 — Book-card animation is progressive enhancement

- Date: 2026-07-14
- Type: design
- Decision: adapt the referenced left-hinged CSS book interaction only for reusable public book cards, with keyboard parity and static coarse-pointer/reduced-motion fallbacks.
- Reason: adds a recognizable book interaction without changing navigation semantics, document assets, or the restrained government portal layout.

---

### D-008 — Simplify access to public, member, and superadmin

- **Date**: 2026-07-14 · **Type**: scope-change
- **Trigger**: user-requested access-level simplification
- **Build stage**: post-completion
- **What changed**: replace visitor/member/admin plus four admin levels with exactly
  `public`, `member`, and `superadmin`.
- **Why**: the user requested a simpler three-level operational model.
- **Before**: public visitor, member account, and admin account with editor,
  content-admin, auditor, or superadmin level.
- **After**: unauthenticated public context, member account, and one superadmin account
  type that owns all dashboard operations.
- **Data migration**: retain members, promote legacy superadmin rows, and disable legacy
  editor/content-admin/auditor rows without deleting their audit history.
- **Tasks affected**: Retrofit AS-001-R through AS-005-R; no new product feature.
- **Folders affected**: app authorization/auth/admin/reader layers, database migrations,
  seeders/factories, views, routes, tests, and documentation.
- **Architecture impact**: yes — remove `admin_level` and replace level mappings with
  role permission mappings while keeping granular user overrides.
- **BRIEF.md updated**: yes
- **Approved by**: human
