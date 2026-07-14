# Product Specification — E-Perpustakaan Digital KPU

> Last updated: 2026-07-14 · Scope modification D-008 · Post-completion retrofit.

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
