# Tasks Checklist: 1M Task API Optimization, Redis Caching & Cookie SPA Auth

## Phase 1: Database Foundation & Index Tuning
- [x] Task 1: Create composite database migration and harden query sorting in `TaskController`
  - [x] Create migration `add_pagination_composite_indexes_to_tasks_table.php` (`deleted_at, created_at`, `assigned_to, deleted_at, created_at`, `deleted_at, due_date`)
  - [x] Restrict `$allowedSorts` to indexed fields and append `id` as deterministic tiebreaker
  - [x] Cap `per_page` at 100
  - [x] Verify `EXPLAIN` query plan on 1M records (confirmed `ref` with `Backward index scan`, 0 filesort)

## Phase 2: Redis Caching Engine
- [x] Task 2: Install Predis and implement `TaskCacheService` with key-versioning
  - [x] Install `predis/predis` package via Composer
  - [x] Configure `REDIS_CLIENT=predis` and `CACHE_STORE=redis`
  - [x] Implement `TaskCacheService` for count, page, and user caching
  - [x] Register model lifecycle event hooks on `Task` and `User` to increment version keys
  - [x] Add `X-Cache: HIT|MISS` headers
  - [x] Implement fail-open exception handling

## Phase 3: Cookie-Based SPA Authentication
- [x] Task 3: Backend Sanctum stateful cookie authentication
  - [x] Enable `statefulApi()` in `bootstrap/app.php`
  - [x] Configure stateful domains in `config/sanctum.php`
  - [x] Configure credentials and origins in `config/cors.php`
  - [x] Configure session settings in `config/session.php` (`http_only`, `same_site => lax`)
  - [x] Update `AuthController` for stateful session login & dual-mode token support
- [x] Task 4: Frontend Vite proxy, Axios client, and Pinia in-memory auth store
  - [x] Configure Vite proxy for `/api` and `/sanctum` in `frontend/vite.config.ts`
  - [x] Update Axios client with `withCredentials` and `withXSRFToken`, removing `localStorage` Bearer interceptor
  - [x] Refactor Pinia `useAuthStore` to manage auth purely in memory
  - [x] Implement `init()` hydration from `/api/v1/me`
  - [x] Update Vue Router guard to await session hydration on app load

## Phase 4: Frontend UX Refinements
- [x] Task 5: Add table skeleton loaders and polished asynchronous states
  - [x] Add skeleton placeholder rows to `TasksPage.vue` matching table columns
  - [x] Add disabled state and loading spinner to `LoginPage.vue`
  - [x] Add session expired notification in `App.vue`

## Phase 5: Verification & Benchmarking
- [x] Task 6: Feature tests and 1M records benchmark
  - [x] Create `TaskCachingTest.php`
  - [x] Create `SpaAuthTest.php`
  - [x] Run full test suite: `php artisan test` (43 passed, 139 assertions)
  - [x] Benchmark `GET /api/v1/tasks` on 1,000,000 tasks dataset (13.84ms db seek, 155ms total warm request)
