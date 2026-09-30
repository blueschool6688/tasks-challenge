# Team Task Manager

Full-stack application: Laravel 11 REST API + Vue 3 SPA (TypeScript, Vuetify 3).

## Project structure

```
backend/     → Laravel 11 (PHP 8.3, Sanctum, SQLite)
frontend/    → Vue 3 + Vite + TypeScript + Vuetify 3 + Pinia
tasks/       → Spec, plan, and task breakdown
docs/        → Agent config and ADRs
```

## Conventions

### Backend (Laravel)
- PHP 8.3 with strict types in all files.
- Eloquent models in `app/Models/`, controllers in `app/Http/Controllers/`.
- Form Request classes for validation and inline authorization.
- API routes in `routes/api.php`, guarded by `auth:sanctum`.
- JSON responses only — no Blade views. HTTP status codes: 200, 201, 204, 401, 403, 404, 422.
- Soft deletes on `Task` model.
- SQLite for local development.

### Frontend (Vue 3)
- `<script setup lang="ts">` in every component.
- Pinia stores in `src/stores/`.
- Axios with Bearer token interceptor in `src/api/`.
- Vue Router with navigation guards.
- Vuetify 3 as the component library — use Vuetify components, not raw HTML.
- TypeScript strict mode. Define interfaces in `src/types/`.

### General
- No `any` type in TypeScript.
- Preserve all existing comments.
- One concern per file.

## Agent skills

### Issue tracker

Issues live as local markdown files under `.scratch/`. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five-role vocabulary. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context layout: `CONTEXT.md` + `docs/adr/`. See `docs/agents/domain.md`.
