# Project Agent Guide — E-Perpustakaan Digital KPU

## Stack

Laravel 13 / PHP 8.3 / MySQL-MariaDB / Blade / Livewire 4 / Tailwind 4 / Vite 8.

## Rules

- Read `vibe/CODEBASE.md`, `docs/ARCHITECTURE.md`, `vibe/SPEC_INDEX.md`, and `vibe/TASKS.md` before changes.
- Use Form Requests at HTTP boundaries and domain services for business workflows.
- Policies and middleware delegate to granular permissions; never hard-code admin level authorization.
- Original PDFs are private, immutable, and never stored under a client-supplied filename.
- Use TDD for behavior and run targeted tests before the full suite.
- Update CODEBASE/TASKS whenever routes, schema, or feature boundaries change.

## Active feature

Tahap 4-6 completion program. Execute `vibe/MASTER_COMPLETION_PLAN.md` in order,
use TDD for each observable behavior, and keep `vibe/TASKS.md` synchronized.
Phase 3 remains archived at `vibe/features/2026-07-13-public-portal-catalog-search/`;
its reusable visual rules remain in `vibe/DESIGN_SYSTEM.md`.
