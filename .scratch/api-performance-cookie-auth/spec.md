# Spec: Fast task listing, Redis caching, and cookie-based SPA authentication

Status: ready-for-agent

Supersedes: `.scratch/task-pagination-performance/spec.md` (its index and count-cache decisions are folded in here).

## Problem Statement

1. **Slow task listing.** `GET /api/v1/tasks?page=1&per_page=10&sort_by=created_at&sort_order=desc` takes ~3 s against 1,000,000 tasks. `EXPLAIN` shows MySQL using `idx_tasks_status_filter (status, deleted_at, created_at)` and examining ~483k rows, because the default admin listing has no `status` filter so the leftmost prefix is unusable. Pagination also runs a full `COUNT(*)` over the soft-delete scope on every request (~1.5 s on its own).
2. **No caching layer.** Every request hits MySQL even for data that rarely changes (the users list, total counts, the first page of the default listing). The cache store is the `database` driver, so it adds load to the same database it is meant to protect.
3. **Token stored in `localStorage`.** The Sanctum token and user object live in `localStorage` and are attached by an Axios Bearer interceptor. Any XSS can read and exfiltrate the token. The user object is also duplicated in `localStorage` instead of being owned by Pinia.

## Solution

1. Make the default listing an index seek instead of a range scan, and stop recomputing the total count on every page view.
2. Introduce Redis as the cache store and cache read-heavy endpoints with version-key invalidation, so writes make stale entries unreachable immediately.
3. Switch the SPA to Sanctum's first-party SPA authentication: an HttpOnly, SameSite=Lax session cookie plus CSRF protection via the `XSRF-TOKEN` cookie. The browser never sees a token. Pinia becomes the single in-memory owner of auth state, hydrated from `GET /me` on app boot. Bearer tokens keep working for Postman and non-browser clients.

## User Stories

1. As an admin, I want the default task list to load in under 100 ms on 1M tasks, so that I can work without waiting.
2. As a regular user, I want my own task list to load in under 100 ms, so that my dashboard feels instant.
3. As an admin filtering by status, I want filtered lists to stay fast, so that filters do not punish me.
4. As an admin filtering by assignee, I want assignee-filtered lists to stay fast, so that I can review one person's workload.
5. As a user paging through results, I want page 2, 3, … to be as fast as page 1, so that browsing is smooth.
6. As a user, I want the total count and last page in the pagination meta to stay accurate after I create, update, delete, or restore a task, so that the paginator never lies.
7. As a user, I want a task I just created to appear in the list immediately, so that caching never hides my own writes.
8. As a user, I want a task I just deleted to disappear immediately, so that caching never shows ghosts.
9. As a regular user, I want cached data to never leak another user's tasks, so that tenancy rules survive caching.
10. As an API client, I want unsupported `sort_by` values to fall back to `created_at desc`, so that I cannot trigger a full-table filesort.
11. As an API client, I want `per_page` capped at 100, so that I cannot request huge pages.
12. As a frontend developer, I want the `data` + `meta` response envelope unchanged, so that the UI needs no contract change for the list.
13. As an operator, I want Redis to be the cache store, so that caching does not add load to MySQL.
14. As an operator, I want the app to keep working (just slower) if Redis is down, so that a cache outage is not a full outage.
15. As an operator, I want slow queries (>100 ms) logged with SQL and timing, so that regressions are caught.
16. As an operator, I want a `Server-Timing` / `X-Cache` style signal on the list endpoint, so that I can see whether a response was a cache hit.
17. As a user picking an assignee, I want the users dropdown to load instantly, so that creating tasks is quick.
18. As an admin, I want the users list cache to refresh when a user is created or changed, so that new teammates appear.
19. As a user, I want my session to be stored in an HttpOnly cookie, so that injected scripts cannot steal it.
20. As a user, I want state-changing requests protected by CSRF, so that other sites cannot act on my behalf.
21. As a user, I want to stay logged in after a page refresh, so that I do not re-login constantly.
22. As a user, I want logout to invalidate my session server-side, so that a copied cookie stops working.
23. As a user whose session expired, I want to be redirected to the login page with a clear message, so that I know why.
24. As a frontend developer, I want Pinia to be the only place auth state lives, so that there is one source of truth.
25. As a frontend developer, I want nothing auth-related in `localStorage` or `sessionStorage`, so that the XSS surface is minimal.
26. As a frontend developer, I want route guards to wait for the session check before deciding, so that refresh does not flash the login page.
27. As a Postman / mobile client, I want Bearer tokens to keep working, so that non-browser integrations are not broken.
28. As a developer running locally, I want the SPA and API to be same-site in dev, so that cookies work without HTTPS hacks.
29. As a user on the login page, I want clear loading and error states while signing in, so that I know what is happening.
30. As a user on the task list, I want a skeleton/loading state instead of a blank table, so that the page feels responsive while data loads.

## Implementation Decisions

### A. Query and index

- New migration adds composite indexes with `deleted_at` before the sort column:
  - `(deleted_at, created_at)` — admin default listing.
  - `(assigned_to, deleted_at, created_at)` — regular user default listing / admin assignee filter.
  - `(status, deleted_at, created_at)` already exists — status filter.
  - `(deleted_at, due_date)` — due-date sort.
- The existing `idx_tasks_user_filter (assigned_to, status, deleted_at, created_at)` stays for user + status filter.
- Sort whitelist shrinks to indexed paths: `created_at`, `due_date`, `id`. `id` is appended as a tiebreaker to every sort so pagination is deterministic.
- `title`/`updated_at` sorting is removed from the whitelist (falls back to default). The frontend does not offer them.
- Selected columns stay explicit; `description` stays in the list (UI shows it truncated).
- Search (`LIKE %term%`) is acknowledged as unindexable; when `search` is present, count caching still applies but the data query is not promised to be <100 ms (see Out of Scope).

### B. Caching (Redis)

- `CACHE_STORE=redis`, client `predis` (no phpredis extension in Laragon PHP 8.3). Redis runs via Laragon locally and as a service in Docker/prod.
- Invalidation via **version keys**, not tags or `flush`:
  - `tasks:version` — incremented on task `created`, `updated`, `deleted`, `restored`, `forceDeleted` (model events, wired in one dedicated observer/listener class).
  - `users:version` — incremented on user `saved` / `deleted`.
- A dedicated cache module (one concern per file) owns key building and remember/invalidate logic. Controllers ask it for data; they do not build keys.
- Cached items:
  | Item | Key shape | TTL |
  |---|---|---|
  | Task list total count | `tasks:v{ver}:count:{scope}:{filterHash}` | 10 min |
  | Task list page payload (pages 1–3 only, no search) | `tasks:v{ver}:page:{scope}:{filterHash}:{page}:{perPage}:{sort}` | 60 s |
  | Users list | `users:v{ver}:list` | 1 h |
- `{scope}` is `all` for admin and `u{id}` for regular users — this is the tenancy guarantee in the cache key.
- `{filterHash}` is a hash of the normalized, whitelisted query params only (never raw query string).
- The paginator is built manually from cached count + seek query, so the `meta` envelope is unchanged.
- Fail-open: cache read/write errors are caught and logged; the request falls through to the database.
- Response header `X-Cache: HIT|MISS` on the list endpoint, plus `Server-Timing` with db and total ms.

### C. Cookie-based SPA authentication

- Use Sanctum SPA (stateful) authentication: `statefulApi()` middleware, `SANCTUM_STATEFUL_DOMAINS` includes the SPA host, `SESSION_DRIVER=redis`, session cookie `HttpOnly`, `SameSite=Lax`, `Secure` in prod.
- Login flow: `GET /sanctum/csrf-cookie` → `POST /api/v1/login` (session-based `Auth::attempt` + session regenerate) → response returns `user` only, no token, for stateful requests.
- A separate token endpoint is kept for non-browser clients (`POST /api/v1/tokens` returns a Sanctum personal access token). `auth:sanctum` accepts either.
- Logout: invalidates session, regenerates CSRF token, and deletes the current access token when called with Bearer.
- Dev same-site: Vite dev server proxies `/api` and `/sanctum` to `http://tasks-challenge.test`, and the SPA uses relative base URL `/api/v1`. In prod the SPA is served from the same site as the API (same domain or subdomain under one registrable domain).
- CORS: `supports_credentials=true` with explicit origins only (no wildcard), exposed `Authorization` header removed.
- Frontend:
  - Axios client: `withCredentials: true`, `withXSRFToken: true`; no Authorization header, no `localStorage` access.
  - On 419 (CSRF mismatch) the client refetches the CSRF cookie once and retries.
  - On 401 the auth store is reset and the router redirects to login (no `window.location` hard reload).
  - Pinia auth store holds `user`, `status: 'unknown' | 'authenticated' | 'guest'`, `isAdmin`; `init()` calls `GET /me` once per app load; the router guard awaits `init()`.
  - All `localStorage` / `sessionStorage` auth code is deleted.
- AGENTS.md convention "Axios with Bearer token interceptor" is updated to "Axios with cookie session + XSRF".

### D. UX touches (scoped from design-taste-frontend)

- Only loading/empty/error states: table skeleton rows while `loading`, disabled submit + spinner on login, session-expired snackbar. No visual redesign — the skill targets landing pages, not dashboards.

## Testing Decisions

- Test external behavior through HTTP feature tests (highest seam: the API). No tests on cache key strings or internal classes.
- Seams:
  1. **API HTTP layer** (`TaskApiTest`, `AuthApiTest`, new `TaskCachingTest`, new `SpaAuthTest`) — primary.
  2. **Pinia auth store** with mocked API module — frontend unit tests (Vitest), only if Vitest is already set up; otherwise covered by manual E2E checklist.
- Caching tests use the `array` cache store in tests (`phpunit.xml`) and assert behavior: create → list shows new total; delete → total decreases; user A never sees user B's cached page; second call returns `X-Cache: HIT`.
- SPA auth tests: csrf-cookie + login sets session; `/me` works with cookie only; logout kills session; Bearer token still works on `/me`; POST without CSRF from stateful origin returns 419.
- Existing 37 tests must keep passing (login test is adapted only where the response no longer contains `token` for stateful requests).
- Performance verification (manual, on the 1M dataset): `EXPLAIN` of the default listing shows the new index and `rows` ≈ per_page; p50 latency < 100 ms cold, < 20 ms warm.

## Out of Scope

- Full-text search engine (Meilisearch/Scout) for `search`.
- Cursor pagination API change (can be added later as `?pagination=cursor`).
- Table partitioning, read replicas.
- HTTP response caching at CDN / reverse proxy.
- Refresh-token rotation (session cookies make it unnecessary).
- Visual redesign of the dashboard.

## Further Notes

- Redis needs the `predis/predis` Composer package; there is no phpredis extension in the local PHP build.
- Sanctum SPA auth requires SPA and API to share a registrable domain. `localhost:5173` → `tasks-challenge.test` is cross-site, which is why the Vite proxy is mandatory in dev.
- Plan and per-task breakdown: `plan.md` and `issues/` in this directory.
