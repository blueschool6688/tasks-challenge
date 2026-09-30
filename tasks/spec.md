# Team Task Manager — Specification

## Problem Statement

A development team needs a simple, clean task management application to assign, track, and manage tasks across team members. The primary audience is a hiring evaluator assessing clean code quality, architectural clarity, and engineering discipline — not feature breadth.

## Solution

A full-stack SPA consisting of a Laravel 11 REST API backend with Sanctum token authentication and a Vue 3 + Vuetify 3 + TypeScript frontend. The application supports two roles (Admin and User) with granular authorization, task CRUD with filtering/search, and a polished but minimal UI.

## User Stories

1. As an **admin**, I want to log in with my email and password, so that I receive a token and can access the admin dashboard.
2. As a **user**, I want to log in with my email and password, so that I can view and manage my assigned tasks.
3. As a **logged-in user**, I want to log out, so that my session token is revoked and I'm redirected to the login page.
4. As an **admin**, I want to see all tasks in a paginated table, so that I can oversee the full team's workload.
5. As a **user**, I want to see only the tasks assigned to me, so that I can focus on my own work.
6. As an **admin**, I want to filter tasks by status (todo/in_progress/done), so that I can find tasks in a specific state.
7. As any **logged-in user**, I want to search tasks by title, so that I can quickly find a specific task.
8. As an **admin**, I want to filter tasks by assignee, so that I can check a specific team member's workload.
9. As an **admin**, I want to create a task and assign it to any user, so that I can delegate work.
10. As a **user**, I want to create a task assigned to myself, so that I can track my own work items.
11. As an **admin**, I want to edit any task (title, description, status, assignee, due date), so that I can manage the entire team's work.
12. As a **user**, I want to edit only tasks assigned to me, so that I can update my own progress.
13. As an **admin**, I want to delete (soft-delete) any task, so that I can clean up the task board.
14. As a **user**, I want to delete only tasks assigned to me, so that I can remove my own completed/obsolete tasks.
15. As a **logged-in user**, I want to see color-coded status chips (grey=todo, blue=in_progress, green=done), so that I can scan task state at a glance.
16. As a **logged-in user**, I want to see a loading spinner during API calls, so that I know the app is working.
17. As a **logged-in user**, I want clear error messages on failures (validation, 403, 404), so that I understand what went wrong.
18. As a **logged-in user**, I want success/failure feedback via snackbar notifications, so that I know my action completed.
19. As an **unauthenticated visitor**, I want to be redirected to `/login` when accessing `/tasks`, so that protected routes are secure.
20. As a **logged-in user**, I want to see my name and role badge in the header, so that I know who I'm logged in as.

## Implementation Decisions

### Architecture
- **Monorepo structure:** Single repository with `backend/` (Laravel 11) and `frontend/` (Vue 3 Vite SPA) directories at the project root.
- **Database:** SQLite for local development simplicity (no MySQL server dependency for reviewers). Migration + Seeder provided.
- **Authentication:** Laravel Sanctum issuing PlainTextToken on login. Frontend stores token in `localStorage` and sends it via `Authorization: Bearer` header.
- **CORS:** Laravel CORS config allows the Vite dev server origin (`http://localhost:5173`).

### Backend (Laravel 11)
- **Models:** `User` (with `role` enum cast) and `Task` (with `status` enum cast, `SoftDeletes` trait).
- **Controllers:** `AuthController` (login/logout), `TaskController` (CRUD resource).
- **Form Requests:** `StoreTaskRequest`, `UpdateTaskRequest` for validation + inline authorization.
- **Authorization:** Inline in controllers/form requests — Admin can do everything; User is scoped to `assigned_to = auth()->id()`. No Policy class needed for this scope (KISS).
- **API Resource:** `TaskResource` for consistent JSON shape.
- **Routes:** All under `api.php`, guarded by `auth:sanctum` middleware except `/login`.
- **Error responses:** JSON only. Validation → 422, forbidden → 403, not found → 404.

### Frontend (Vue 3 + Vuetify 3 + TypeScript)
- **Build tool:** Vite.
- **State management:** Pinia stores (`useAuthStore`, `useTaskStore`).
- **HTTP client:** Axios with interceptor for Bearer token and 401 auto-logout.
- **Routing:** Vue Router with `beforeEach` navigation guard checking auth state.
- **Components:**
  - `LoginPage.vue` — email/password form.
  - `TasksPage.vue` — data table with filters, search, pagination.
  - `TaskForm.vue` — reusable dialog/modal for create + edit.
  - `AppHeader.vue` — user info, role badge, logout button.
- **UI Library:** Vuetify 3 (`v-data-table`, `v-chip`, `v-dialog`, `v-snackbar`, `v-date-input`, `v-select`, `v-text-field`).

### API Contract
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/login` | No | Returns `{ token, user: { id, name, email, role } }` |
| POST | `/api/logout` | Yes | Revokes current token, returns 204 |
| GET | `/api/users` | Yes | Returns `[{ id, name }]` |
| GET | `/api/tasks` | Yes | Query: `?status=&assigned_to=&search=&page=&per_page=` |
| POST | `/api/tasks` | Yes | Body: `{ title, description?, status, assigned_to, due_date? }` |
| PUT | `/api/tasks/{id}` | Yes | Body: same as POST |
| DELETE | `/api/tasks/{id}` | Yes | Soft delete, returns 204 |

## Testing Decisions

- **Seam:** The REST API is the primary testing seam. Feature tests exercise controllers through HTTP, validating authorization, validation, and response shape.
- **What makes a good test:** Tests verify external behavior (HTTP request → response status + JSON shape), not internal implementation details.
- **Backend tests:** Laravel Feature tests for each endpoint (auth, CRUD, authorization scoping, validation errors, filtering).
- **Frontend:** Manual verification via the browser (not automated E2E in this scope — KISS for a hiring challenge).

## Out of Scope

- Automated frontend E2E tests (Cypress/Playwright).
- Real-time updates (WebSocket/Pusher).
- File uploads or task attachments.
- Task comments or activity log.
- User registration or profile management.
- Password reset flow.
- Deployment configuration (Docker, CI/CD).
- Advanced features: task priority, labels, kanban board view.

## Further Notes

- The project includes `ANSWERS.md` addressing 4 interview questions about API optimization at scale, security best practices, TypeScript benefits, and Vue 3 `<script setup>` typed props.
- SQLite is chosen over MySQL so that a reviewer can `php artisan migrate --seed` without configuring a database server.
- The `README.md` must provide complete setup instructions so the reviewer can run the project in under 5 minutes.
