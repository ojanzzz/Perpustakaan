# Feature Spec — Content Administration, PDF Upload, and Dashboard

> Historical specification. Publication workflow statements below were superseded by
> decision D-009 on 2026-07-19: every newly added book is published immediately and
> manual draft, review, scheduling, and archive controls are no longer active.

## Overview

Phase 2 enables authorized administrators to manage books, categories, and collections,
upload PDF originals into private storage, queue document processing, and monitor summary
metrics from a responsive dashboard.

## User stories

- Editor creates a draft, uploads a valid PDF, edits metadata, previews status, and submits later.
- Content admin manages taxonomy/collections and all editor capabilities.
- Auditor sees dashboard and content lists but cannot mutate content.
- Superadmin has every content capability through permissions, never level checks.

## Acceptance criteria

1. Book, category, collection, author, publisher, tag, language, and version models expose relationships.
2. Admin book list paginates and supports title/status filtering.
3. Authorized editor can create a draft with metadata and one valid PDF.
4. Invalid MIME, extension, oversize, malformed signature, or unreadable PDF is rejected clearly.
5. Original PDF uses a UUID path on the private disk; original client filename is never a storage path.
6. A book version and queued processing job are created atomically.
7. Processing extracts page count, file size/hash, and first-page WebP cover when tools are available.
8. Processing state is visible and failure details are retained without losing the original.
9. Category supports hierarchy and collection supports visibility/order.
10. Category and collection CRUD are permission-protected and validated.
11. Dashboard shows total/draft/published/private books, users, today's readers/downloads, failures, and backup state.
12. Seed data creates 10 categories, 5 collections, and 20 metadata-only demo books without copyrighted files.
13. All forms have CSRF, validation errors, accessible labels, and responsive layouts.

## Deferred

- Publication review/publish scheduling is completed with its dedicated workflow in the next content increment.
- Bulk CSV/XLSX and remote URL import are Phase 5 administration enhancements.
- Full reader, public catalog, OCR, watermark, and PDF text search are later phases.
- Ghostscript/qpdf optimization requires an installed configured binary; original and thumbnails remain functional without it.

## Security and performance

- Policies and `permission:*` middleware protect every mutation.
- Private disk is authoritative for originals and versions.
- Validation runs before persistence; heavy thumbnail/metadata work runs in queue.
- Lists paginate and eager-load relations; indexes from Phase 1 are reused.
