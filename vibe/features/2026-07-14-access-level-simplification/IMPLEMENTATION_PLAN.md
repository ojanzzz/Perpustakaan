# Three-Level Access Model Implementation Plan

Goal: Replace the completed legacy administrator hierarchy with exactly `public`,
`member`, and `superadmin` without breaking public, member, or governance features.

Approved direction: public is unauthenticated, member remains account-backed,
superadmin is the only dashboard role, and legacy non-superadmin administrators are
disabled during upgrade.

## Task 1 — Access and schema contracts

Files: foundation schema/role tests, `UserRole`, user migration, authorization migration.

1. Add failing tests for the three enum values, absence of `admin_level`, role permission
   schema, and upgrade conversion of existing users.
2. Run the focused foundation tests and confirm they fail against the legacy model.
3. Add an additive upgrade migration and make fresh migrations converge on the same schema.
4. Re-run focused tests; expect green.

## Task 2 — Authorization boundaries

Files: `User`, `PermissionService`, member/admin middleware, 2FA/auth controllers,
book/document access services, policies, and route contracts.

1. Add failing tests proving only superadmin can use administrative permissions and
   members/public retain their existing access.
2. Replace legacy admin checks and level mapping with role mapping.
3. Run foundation, auth, public-access, reader, and delivery tests.

## Task 3 — Administration and demo data

Files: permission/demo seeders, user factory/controller/view, public/admin layouts.

1. Add failing seed and account-management tests for one superadmin and one member.
2. Remove legacy demo accounts and level selectors; keep granular override UI.
3. Ensure demo/activity/catalog seeders target the superadmin role deterministically.
4. Verify seeding twice is idempotent.

## Task 4 — Workflow regression conversion

Files: content, governance, backup, analytics, audit, and 2FA tests plus affected copy.

1. Convert editor/content-admin/auditor scenarios to superadmin success and member/public denial.
2. Preserve content review state transitions, audit immutability, exports, and backups.
3. Run each affected feature group before the full suite.

## Task 5 — Delivery verification

1. Run migration+seed from an empty SQLite database.
2. Run an upgrade fixture containing all legacy roles and verify conversion/disable rules.
3. Run `php artisan test`, `vendor/bin/pint --test`, and `npm run build`.
4. Search source for forbidden legacy authorization references.
5. Update architecture, development, deployment, README, CODEBASE, and task status.
