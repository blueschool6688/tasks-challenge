# Task List: Team Task Manager

## Phase 1: Project Scaffolding & Foundation

## Task 1: Initialize Git repo and scaffold Laravel 11 backend

**Description:** Initialize a git repository, create a Laravel 11 project in `backend/`, configure SQLite database, install Sanctum, and set up CORS for the Vue SPA dev server.

**Acceptance criteria:**
- [x] Git repo initialized with `.gitignore`
- [x] Laravel 11 installed in `backend/` with SQLite configured in `.env`
- [x] Sanctum installed and configured
- [x] CORS configured to allow `http://localhost:5173`
- [x] `php artisan serve` starts without errors

**Verification:**
- [x] `php artisan serve` runs and returns Laravel welcome at `http://localhost:8000`
- [x] `php artisan sanctum:prune-expired` runs without error (Sanctum installed)

**Dependencies:** None

**Files likely touched:**
- `backend/.env`, `backend/.env.example`
- `backend/config/cors.php`
- `backend/config/sanctum.php`
- `backend/app/Models/User.php` (HasApiTokens trait)

**Estimated scope:** Medium (3-5 files)

---

## Task 2: Create database schema (migrations + models)

**Description:** Create migrations for `users` (add `role` column) and `tasks` tables. Update the User model with `role` enum cast and `tasks()` relationship. Create the Task model with `status` enum cast, `SoftDeletes`, and `user()` relationship.

**Acceptance criteria:**
- [x] `users` table has `role` column (string, default 'user')
- [x] `tasks` table has `title`, `description`, `status`, `assigned_to`, `due_date`, `deleted_at` columns
- [x] `tasks.assigned_to` has foreign key to `users.id`
- [x] User model: `role` cast to string, `tasks()` hasMany relationship
- [x] Task model: `status` cast, `SoftDeletes` trait, `user()` belongsTo relationship, `$fillable` set

**Verification:**
- [x] `php artisan migrate` runs without errors
- [x] `php artisan migrate:rollback` and re-migrate succeeds
- [x] Schema inspection shows correct columns and constraints

**Dependencies:** Task 1

**Files likely touched:**
- `backend/database/migrations/xxxx_add_role_to_users_table.php`
- `backend/database/migrations/xxxx_create_tasks_table.php`
- `backend/app/Models/User.php`
- `backend/app/Models/Task.php`

**Estimated scope:** Small (4 files)

---

## Task 3: Create database seeder with test data

**Description:** Create a DatabaseSeeder that creates 3 users (admin@example.com as admin, john@example.com and jane@example.com as users) and 10-15 sample tasks with varied statuses distributed across users.

**Acceptance criteria:**
- [x] `admin@example.com` created with role=admin, password=password
- [x] `john@example.com` and `jane@example.com` created with role=user, password=password
- [x] 10-15 tasks created with mixed statuses (todo/in_progress/done)
- [x] Tasks distributed across all 3 users
- [x] Some tasks have due_date, some null

**Verification:**
- [x] `php artisan migrate:fresh --seed` runs without errors
- [x] Database contains 3 users and 10-15 tasks

**Dependencies:** Task 2

**Files likely touched:**
- `backend/database/seeders/DatabaseSeeder.php`

**Estimated scope:** XS (1 file)

---

## Checkpoint: Foundation
- [x] `php artisan migrate:fresh --seed` succeeds
- [x] 3 users + 10-15 tasks in database
- [x] Models have correct relationships

---

## Phase 2: Backend API — Auth & CRUD

## Task 4: Implement authentication API (login/logout with Sanctum)

**Description:** Create AuthController with login (validates credentials, creates Sanctum PlainTextToken, returns token + user info) and logout (revokes current token). Define routes in `api.php`.

**Acceptance criteria:**
- [x] `POST /api/login` with valid credentials returns `{ token, user: { id, name, email, role } }` with 200
- [x] `POST /api/login` with invalid credentials returns 401 with error message
- [x] `POST /api/login` validates email (required, email) and password (required)
- [x] `POST /api/logout` with valid Bearer token revokes the token and returns 204
- [x] `POST /api/logout` without token returns 401

**Verification:**
- [x] Automated PHPUnit Feature tests for login success, login failure, logout
- [x] Token can be used in subsequent authenticated requests

**Dependencies:** Task 3

**Files likely touched:**
- `backend/app/Http/Controllers/AuthController.php`
- `backend/app/Http/Requests/LoginRequest.php`
- `backend/routes/api.php`

**Estimated scope:** Small (3 files)

---

## Task 5: Implement users list endpoint

**Description:** Create a simple endpoint `GET /api/users` that returns a list of users with `id` and `name` only, for populating the assignee dropdown in the frontend. Protected by `auth:sanctum`.

**Acceptance criteria:**
- [x] `GET /api/users` returns `[{ id, name }, ...]` with 200
- [x] Requires authentication (returns 401 without token)
- [x] Returns all users regardless of role

**Verification:**
- [x] Automated PHPUnit Feature test returns user list
- [x] Without token returns 401

**Dependencies:** Task 4

**Files likely touched:**
- `backend/routes/api.php` (add route)
- `backend/app/Http/Controllers/UserController.php`

**Estimated scope:** XS (2 files)

---

## Task 6: Implement task CRUD endpoints with authorization

**Description:** Create TaskController with index, store, show (optional), update, and destroy methods. Create StoreTaskRequest and UpdateTaskRequest for validation. Implement authorization: Admin has full access; User is scoped to own tasks. Create TaskResource for consistent JSON output.

**Acceptance criteria:**
- [x] `POST /api/tasks`: Admin can assign to any user; User can only assign to self (403 if assigning to others)
- [x] `PUT /api/tasks/{id}`: Admin can update any task; User can only update own tasks (403 otherwise)
- [x] `DELETE /api/tasks/{id}`: Admin can delete any; User can only delete own (403 otherwise)
- [x] `GET /api/tasks`: Admin sees all; User sees only own tasks
- [x] StoreTaskRequest validates: title (required|string|max:255), description (nullable|string), status (required|in:todo,in_progress,done), assigned_to (required|exists:users,id), due_date (nullable|date)
- [x] UpdateTaskRequest validates same fields but all optional
- [x] Delete is soft-delete
- [x] 404 returned for non-existent task IDs
- [x] TaskResource formats response consistently

**Verification:**
- [x] Automated PHPUnit tests for each endpoint as admin and as user
- [x] Validation errors return 422 with field-level errors
- [x] Authorization violations return 403
- [x] Deleted tasks don't appear in GET index

**Dependencies:** Task 4, Task 5

**Files likely touched:**
- `backend/app/Http/Controllers/TaskController.php`
- `backend/app/Http/Requests/StoreTaskRequest.php`
- `backend/app/Http/Requests/UpdateTaskRequest.php`
- `backend/app/Http/Resources/TaskResource.php`
- `backend/routes/api.php`

**Estimated scope:** Medium (5 files)

---

## Task 7: Add filtering, search, and pagination to GET /api/tasks

**Description:** Enhance the TaskController `index` method to support query parameters for filtering by status, assigned_to, text search on title, and pagination.

**Acceptance criteria:**
- [x] `?status=todo` filters by status
- [x] `?assigned_to=2` filters by assignee (admin only; users already scoped)
- [x] `?search=keyword` searches by title (LIKE %keyword%)
- [x] Results are paginated (default 15 per page)
- [x] Filters can be combined
- [x] User's scope filter (own tasks only) cannot be bypassed by query params

**Verification:**
- [x] Automated PHPUnit tests with various filter combinations
- [x] Pagination metadata returned (`current_page`, `last_page`, `total`, etc.)
- [x] User cannot see other users' tasks even with `?assigned_to=other_id`

**Dependencies:** Task 6

**Files likely touched:**
- `backend/app/Http/Controllers/TaskController.php` (update index method)

**Estimated scope:** XS (1 file)

---

## Checkpoint: Backend API Complete
- [x] All API endpoints working and returning correct status codes
- [x] Admin vs User authorization verified
- [x] Filtering, search, and pagination working
- [x] Error responses are JSON with correct HTTP status codes

---

## Phase 3: Frontend SPA — Core

## Task 8: Scaffold Vue 3 + Vuetify 3 + TypeScript frontend

**Description:** Create a Vite + Vue 3 + TypeScript project in `frontend/`. Install and configure Vuetify 3, Vue Router, Pinia, and Axios. Set up the project structure with proper TypeScript types.

**Acceptance criteria:**
- [x] `frontend/` contains a working Vite + Vue 3 + TypeScript project
- [x] Vuetify 3 installed and configured as a plugin
- [x] Vue Router configured with `/login` and `/tasks` routes
- [x] Pinia installed and configured
- [x] Axios configured with base URL pointing to `http://localhost:8000/api`
- [x] TypeScript interfaces defined for `User`, `Task`, `LoginCredentials`, `ApiResponse`
- [x] `npm run dev` configuration ready

**Verification:**
- [x] All TypeScript interfaces, config files, and plugins created
- [x] Vuetify components and themes properly configured
- [x] Strict TypeScript without any `any` types

**Dependencies:** None (can be done parallel to backend tasks)

**Files likely touched:**
- `frontend/package.json`
- `frontend/vite.config.ts`
- `frontend/src/main.ts`
- `frontend/src/plugins/vuetify.ts`
- `frontend/src/router/index.ts`
- `frontend/src/stores/` (directories)
- `frontend/src/types/index.ts`
- `frontend/src/api/axios.ts`

**Estimated scope:** Medium (8 files)

---

## Task 9: Implement auth store, Axios setup, and Login page

**Description:** Create the auth Pinia store (manages token in localStorage, user state, login/logout actions). Configure Axios interceptor to attach Bearer token and handle 401. Build the Login page with Vuetify form components.

**Acceptance criteria:**
- [x] `useAuthStore` manages `token`, `user`, `isAuthenticated` (computed), login/logout actions
- [x] Token persisted in `localStorage`, loaded on app init
- [x] Axios interceptor attaches `Authorization: Bearer {token}` to all requests
- [x] Axios interceptor handles 401 by clearing auth and redirecting to `/login`
- [x] Login page has email + password fields with validation
- [x] Login page shows error message on invalid credentials
- [x] Successful login stores token and redirects to `/tasks`

**Verification:**
- [x] Login with `admin@example.com` / `password` presets and validation rules
- [x] Error feedback alerts on invalid credentials
- [x] Token stored in localStorage and attached to headers

**Dependencies:** Task 4, Task 8

**Files likely touched:**
- `frontend/src/stores/auth.ts`
- `frontend/src/api/axios.ts`
- `frontend/src/pages/LoginPage.vue`

**Estimated scope:** Small (3 files)

---

## Task 10: Implement router with navigation guards and AppHeader

**Description:** Configure Vue Router navigation guards to redirect unauthenticated users to `/login`. Build the AppHeader component showing user name, role badge, and logout button.

**Acceptance criteria:**
- [x] Unauthenticated user visiting `/tasks` is redirected to `/login`
- [x] Authenticated user visiting `/login` is redirected to `/tasks`
- [x] AppHeader shows current user name
- [x] AppHeader shows role badge (Admin/User) with distinct styling
- [x] Logout button calls auth store logout and redirects to `/login`
- [x] AppHeader only visible on authenticated routes

**Verification:**
- [x] Router guards configured with `requiresAuth` and `guestOnly` metadata
- [x] Header dynamically renders user details and role badges
- [x] Logout revokes session and redirects to `/login`

**Dependencies:** Task 9

**Files likely touched:**
- `frontend/src/router/index.ts` (add guards)
- `frontend/src/components/AppHeader.vue`
- `frontend/src/App.vue`

**Estimated scope:** Small (3 files)

---

## Task 11: Implement Tasks page with data table, filters, and search

**Description:** Create the tasks Pinia store and build the TasksPage with Vuetify `v-data-table-server`, status filter dropdown, assignee filter (admin only), search text field, and color-coded status chips. Implement pagination.

**Acceptance criteria:**
- [x] `useTaskStore` manages tasks list, loading state, filters, pagination, and CRUD actions
- [x] Data table shows columns: Title, Assignee, Status, Due Date, Actions (Edit/Delete)
- [x] Status chips: grey for todo, blue for in_progress, green for done
- [x] Status dropdown filter triggers API re-fetch
- [x] Search input triggers API re-fetch (with debounce)
- [x] Assignee filter visible only to admin users
- [x] Pagination works (server-side via API)
- [x] Loading spinner shown during API calls
- [x] Delete button with confirmation triggers soft-delete and refreshes list
- [x] Error states shown on API failure

**Verification:**
- [x] Table renders all required columns and responsive chips
- [x] Search input debounced at 350ms
- [x] Admin sees all assignees filter; regular user filter scoped
- [x] Soft delete modal dialog with confirmation

**Dependencies:** Task 7, Task 10

**Files likely touched:**
- `frontend/src/stores/tasks.ts`
- `frontend/src/pages/TasksPage.vue`

**Estimated scope:** Medium (2 files, but complex)

---

## Task 12: Implement TaskForm dialog for create/edit with feedback

**Description:** Build a reusable TaskForm.vue modal dialog component used for both creating and editing tasks. Includes title, description, status select, assignee select (fetched from API), and due date picker. Shows snackbar notifications on success/failure.

**Acceptance criteria:**
- [x] TaskForm works in both "create" and "edit" modes
- [x] "Create" mode: empty form, submits POST /api/tasks
- [x] "Edit" mode: pre-populated with task data, submits PUT /api/tasks/{id}
- [x] Title field required with validation
- [x] Status select with todo/in_progress/done options
- [x] Assignee select populated from GET /api/users
- [x] Due date picker using date input
- [x] Admin sees all users in assignee dropdown; User sees only self (dropdown disabled)
- [x] Success snackbar on create/edit
- [x] Error snackbar on validation failure or API error
- [x] Dialog closes after successful submit
- [x] Tasks list refreshes after create/edit

**Verification:**
- [x] Reusable modal component with validation
- [x] Role-restricted assignee dropdown
- [x] Global snackbar notifications on success/failure

**Dependencies:** Task 11

**Files likely touched:**
- `frontend/src/components/TaskForm.vue`
- `frontend/src/pages/TasksPage.vue` (integrate form)

**Estimated scope:** Medium (2 files)

---

## Checkpoint: Frontend Complete
- [x] Full login → task list → create/edit/delete → logout flow works
- [x] Admin and User permissions correctly enforced in UI
- [x] All UI states handled (loading, empty, error)
- [x] Snackbar feedback on all actions

---

## Phase 4: Documentation & Polish

## Task 13: Write README.md with setup instructions

**Description:** Write a clear README.md at the project root with step-by-step setup instructions for both backend and frontend, test account credentials, tech stack summary, and any assumptions made.

**Acceptance criteria:**
- [x] Backend setup: install, configure .env, migrate, seed, serve
- [x] Frontend setup: install, dev server
- [x] Test accounts listed with credentials
- [x] Tech stack and project structure described
- [x] Assumptions documented

**Verification:**
- [x] A reviewer can follow the README and have the app running in < 5 minutes

**Dependencies:** Task 12

**Files likely touched:**
- `README.md`

**Estimated scope:** XS (1 file)

---

## Task 14: Write ANSWERS.md with interview question responses

**Description:** Write comprehensive answers to the 4 interview questions: API optimization at 1M+ records, security best practices for Laravel API + Vue SPA, TypeScript benefits in Vue 3, and a code sample of typed Vue 3 component.

**Acceptance criteria:**
- [x] 4.1.1: 5-10 specific solutions for optimizing GET /api/tasks at 1M+ records (indexing, pagination, caching, queue, read replicas, etc.)
- [x] 4.1.2: Comprehensive security solutions (SQL injection, XSS, CSRF, token storage, HTTPS, rate limiting, etc.)
- [x] 4.2.1: Clear benefits of TypeScript in Vue 3 SPA (type safety, IDE support, refactoring, etc.)
- [x] 4.2.2: Working code sample with `<script setup lang="ts">`, `defineProps<>()` with explicit types

**Verification:**
- [x] All 4 questions answered with depth and specifics
- [x] Code sample compiles without TypeScript errors

**Dependencies:** None (can be done anytime)

**Files likely touched:**
- `ANSWERS.md`

**Estimated scope:** XS (1 file)

---

## Checkpoint: Complete
- [x] All 14 tasks completed
- [x] Full app works end-to-end
- [x] README verified by following setup steps
- [x] ANSWERS.md complete with all 4 questions
- [x] Ready for submission
