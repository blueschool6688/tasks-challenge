# Implementation Plan: Team Task Manager

## Overview

Build a full-stack "Team Task Manager" application with a Laravel 11 REST API backend and a Vue 3 + Vuetify 3 + TypeScript SPA frontend. The project prioritizes clean code, clear architecture, and correct authorization over feature volume.

## Architecture Decisions

- **Monorepo layout:** `backend/` (Laravel 11) + `frontend/` (Vue 3 Vite SPA) at project root. Keeps everything in one repo for easy reviewer setup.
- **SQLite for local dev:** Zero-config database. Reviewer runs `php artisan migrate --seed` with no MySQL setup.
- **Sanctum PlainTextToken:** Stateless token auth. Frontend stores in `localStorage`, sends via `Authorization: Bearer` header. Simple, fits SPA use case.
- **Inline authorization in controllers/form-requests:** Admin → full access, User → scoped to `assigned_to = auth()->id()`. No Policy classes needed (KISS for 2 models).
- **Pinia stores:** `useAuthStore` (token, user, login/logout), `useTaskStore` (tasks, filters, CRUD operations).
- **Axios interceptor:** Attaches Bearer token, handles 401 auto-logout globally.

## Environment

- PHP 8.3.16 (NTS) at `C:\laragon\bin\php\php-8.3.16-nts-Win32-vs16-x64\php.exe`
- Composer at `C:\laragon\bin\composer\composer.bat`
- Node.js v22.12.0
- MySQL 8.0.40 available, but SQLite used for portability
- Laragon on Windows

## Task List

### Phase 1: Project Scaffolding & Foundation

- [x] Task 1: Initialize Git repo and scaffold Laravel 11 backend
- [x] Task 2: Create database schema (migrations + models)
- [x] Task 3: Create database seeder with test data

### Checkpoint: Foundation
- [x] Migrations run cleanly on SQLite
- [x] Seeder creates 3 users + 10-15 tasks
- [x] Models have correct relationships and casts

### Phase 2: Backend API — Auth & CRUD

- [x] Task 4: Implement authentication API (login/logout with Sanctum)
- [x] Task 5: Implement users list endpoint
- [x] Task 6: Implement task CRUD endpoints with authorization
- [x] Task 7: Add filtering, search, and pagination to GET /api/tasks

### Checkpoint: Backend API Complete
- [x] All endpoints return correct HTTP status codes
- [x] Admin sees all tasks, User sees only own tasks
- [x] Validation returns 422, forbidden returns 403, not found returns 404
- [x] Tested via automated test suite (25 tests, 61 assertions)

### Phase 3: Frontend SPA — Core

- [x] Task 8: Scaffold Vue 3 + Vuetify 3 + TypeScript frontend
- [x] Task 9: Implement auth store, Axios setup, and Login page
- [x] Task 10: Implement router with navigation guards and AppHeader
- [x] Task 11: Implement Tasks page with data table, filters, and search
- [x] Task 12: Implement TaskForm dialog for create/edit with feedback

### Checkpoint: Frontend Complete
- [x] Login/logout flow works end-to-end
- [x] Tasks table shows data with pagination
- [x] Filters and search work correctly
- [x] Create/edit/delete tasks with snackbar feedback
- [x] Admin vs User permissions enforced in UI

### Phase 4: Documentation & Polish
- [x] Task 13: Write README.md with setup instructions
- [x] Task 14: Write ANSWERS.md with interview question responses

### Checkpoint: Complete
- [x] Full app works end-to-end
- [x] README setup instructions verified
- [x] All acceptance criteria met
- [x] Ready for review

## Risks and Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| PHP/Composer not in PATH | High | Use absolute paths to Laragon binaries |
| Vuetify 3 v-data-table server-side pagination complexity | Med | Use client-side pagination initially, upgrade if needed |
| SQLite enum limitations | Low | Use string column + cast in Eloquent, no native enum |
| CORS issues between Vite dev server and Laravel | Med | Configure Sanctum's `stateful` domains and CORS config early |

## Open Questions

- None — the spec is fully defined by the user's prompt. Proceeding with implementation.
