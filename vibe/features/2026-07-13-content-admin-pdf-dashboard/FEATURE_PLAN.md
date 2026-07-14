# Feature Plan — Content Administration, PDF Upload, and Dashboard

## Data layer

Create catalog enums, Eloquent models/relationships, and factories against the existing
Phase 1 schema. Add only fields needed for safe original filename and workflow note if a
migration proves necessary.

## Backend

- `PdfValidationService`: MIME/signature/parseability checks and page count probe.
- `PdfIngestionService`: transaction, UUID private storage, version row, queued job.
- `ProcessPdf`: hash, metadata, first page image, WebP conversion, status/error handling.
- Form Requests for book/category/collection validation.
- Policies delegate to `PermissionService`.
- Resource controllers and route groups use auth + granular permission middleware.

## Frontend

- Responsive admin shell with navy sidebar, white work surface, red actions, restrained gold status accents.
- Dashboard summary cards and activity/status panels.
- Functional book table, filter, create/edit forms, upload state, categories, and collections.
- Livewire upload component emits actual upload progress events.

## Tests

- Relationship and factory tests.
- Book create/upload tests for valid, corrupt, wrong MIME, unauthorized, and queued processing.
- Category/collection CRUD authorization tests.
- Dashboard metric test.
- Job success/failure tests using fake storage and configurable tool adapters.

## Rollback

Routes/controllers/views/models can be removed additively. Uploaded files are deleted when
the database transaction fails. Schema rollback remains governed by existing migrations.

