# Product Specification — E-Perpustakaan Digital KPU

> Last updated: 2026-07-19 · Scope modification D-009 · Post-completion retrofit.

## Access model

> ~~Original: visitor/member/admin with editor, content_admin, auditor, and superadmin admin levels.~~
>
> Changed 2026-07-14: exactly three access levels — `public`, `member`, and
> `superadmin`. The public level is an unauthenticated request context, member is a
> registered account, and superadmin is the only dashboard administrator.

Acceptance criteria:

- `UserRole` exposes only `public`, `member`, and `superadmin`.
- Public visitors do not require or receive a database account.
- Registration creates member accounts only.
- Superadmin receives all granular administrative permissions and is the only role
  allowed to enter the administrator dashboard.
- The `admin_level` column and `admin_level_permissions` table are removed after a
  safe data migration.
- Existing superadmin accounts become `role=superadmin`; legacy editor,
  content-admin, and auditor accounts are disabled and lose administrative access.
- Member features, public catalog access, private document authorization, 2FA, and
  per-user permission overrides remain functional.
- Fresh migration, upgrade migration, demo seeding, and the complete automated test
  suite pass with no references to removed access levels.

## Functional scope

The existing catalog, PDF reader, member library, administrator content management,
analytics, audit log, backup, feedback, embed, PWA, and deployment scope remains
unchanged. Only authorization ownership is simplified: every administrative operation
is performed by a superadmin.

## Direct publication

> ~~Original: books entered a draft, review, return, publish/schedule, and archive workflow.~~
>
> Changed 2026-07-19: a successfully uploaded book is published immediately. Manual
> publication workflow controls and endpoints are removed from the administrator UI.

Acceptance criteria:

- Upload from a local PDF or supported PDF URL creates a `published` book with
  `published_at` set immediately.
- The PDF may continue processing asynchronously without reverting publication status.
- Submit, return, publish/schedule, and archive routes are unavailable.
- The edit page contains metadata/PDF replacement only and no workflow or review panel.
- Existing book status and review history remain stored for audit compatibility; this
  retrofit does not bulk-publish or delete historical records.
- Public and administrator layouts use the supplied `/images/logo.png` asset with
  accessible alternative text while retaining light and dark mode.
