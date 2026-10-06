# Implementation Plan: 1M Task API Optimization, Redis Caching & Cookie SPA Auth

## Overview
Transform the Team Task Manager into a production-grade, highly scalable system capable of serving paginated task listings over a dataset of 1,000,000 tasks in under 50ms (down from ~3,000ms). The overhaul implements high-performance composite database indexing, version-keyed Redis caching, and hardened first-party Sanctum SPA authentication using HttpOnly cookies and in-memory Pinia state management (completely removing `localStorage` tokens).

## Architecture Decisions
- **Composite Index Realignment:** Add composite B-tree indexes matching query leftmost prefix rules (`deleted_at, created_at`, `assigned_to, deleted_at, created_at`, and `deleted_at, due_date`). This eliminates MySQL range scans across 483,342 rows, turning listings into direct index seeks examining only 10 rows.
- **Predis Client with Atomic Key-Versioning:** Use `predis/predis` to integrate Redis caching into local Laragon and production environments. Employ atomic version counters (`tasks:version`, `users:version`) bumped via model events to invalidate cache instantly without expensive tag flushes or stale data race conditions.
- **Fail-Open Caching:** Cache layer gracefully catches connection or operational exceptions and falls back to MySQL, ensuring Redis issues never cause application downtime.
- **Sanctum Stateful Cookie Authentication:** Move SPA browser clients to HttpOnly, SameSite=Lax session cookies with XSRF protection. Bearer token support remains intact for non-browser integrations (Postman, API testing).
- **Vite Dev Reverse Proxy:** Proxy `/api` and `/sanctum` in Vite dev server to guarantee same-origin cookie transmission during development without third-party cookie restrictions.
- **Pure In-Memory Pinia Auth:** Auth state lives solely in Vue's Pinia reactive memory, hydrated on app boot via `GET /api/v1/me`. Eliminates all tokens and user JSON from `localStorage`.
- **Skeleton UX:** Replace loading spinners and layout shifts with smooth table skeletons during data fetches to provide seamless feedback.

## Task List

### Phase 1: Database Foundation & Index Tuning
- [ ] Task 1: Create composite database migration and harden query sorting in `TaskController`

### Checkpoint: Database Foundation
- [ ] Migration runs cleanly
- [ ] MySQL `EXPLAIN` confirms index seek on default query (`rows <= 10`)

### Phase 2: Redis Caching Engine
- [ ] Task 2: Install Predis and implement `TaskCacheService` with atomic key-versioning invalidation

### Checkpoint: Caching Engine
- [ ] `tasks:version` increments on model write events
- [ ] `GET /api/v1/tasks` returns `X-Cache: HIT` on repeat calls
- [ ] Tenancy boundary verified (User cannot see cached admin queries)

### Phase 3: Cookie-Based SPA Authentication
- [ ] Task 3: Configure Sanctum stateful session authentication and CORS in Laravel backend
- [ ] Task 4: Configure Vite proxy, Axios cookie/XSRF client, and Pinia in-memory auth store in Vue SPA

### Checkpoint: Authentication & State Management
- [ ] Login sets HttpOnly session cookie; no token in response body for SPA
- [ ] Refreshing browser page keeps user logged in via `init()` hydrate
- [ ] Zero auth tokens or user payloads stored in `localStorage`

### Phase 4: Frontend UX Refinements
- [ ] Task 5: Add table skeleton rows and polished asynchronous states in Vue components

### Phase 5: Verification & Benchmarking
- [ ] Task 6: Write feature tests for caching and SPA auth, and benchmark 1M records dataset

### Checkpoint: Final Acceptance
- [ ] 100% test pass rate (`php artisan test`)
- [ ] Latency on `GET /api/v1/tasks` on 1,000,000 tasks drops from ~3,000ms to < 50ms

## Risks and Mitigations
| Risk | Impact | Mitigation |
|------|--------|------------|
| Redis temporarily offline | Med | Wrap cache calls in try-catch; fail-open directly to MySQL queries |
| Browser blocks cross-site cookies in dev | High | Vite dev server reverse proxy (`/api` and `/sanctum` routed to backend) makes requests same-origin |
| Stale pagination count on high writes | Med | Atomic `tasks:version` increment on model saved/deleted invalidates cached totals instantly |
| Breaking existing API tests with cookie change | High | Dual-mode login & Sanctum auth: session cookies for SPA, Bearer tokens preserved for automated tests/Postman |

## Open Questions
None. Architecture, index schema, and caching strategies are fully defined in the spec.
