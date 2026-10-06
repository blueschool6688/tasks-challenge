# Issue 06: Automated Test Verification and 1M Record Benchmarking

Status: resolved
Type: task

## Description
Write automated feature tests covering Redis caching behaviors, tenancy cache isolation, and cookie-based SPA session authentication. Run the complete backend test suite and benchmark the `GET /api/v1/tasks` endpoint against the 1,000,000 tasks MySQL dataset to verify sub-50ms latency.

## Acceptance Criteria
- [ ] New feature tests (`backend/tests/Feature/TaskCachingTest.php`):
  - Verify total count and page 1 payload are cached.
  - Verify second identical request receives `X-Cache: HIT`.
  - Verify task creation, update, and deletion increment cache version and invalidate previous cached results.
  - Verify multi-tenant cache isolation: user A never sees cached results belonging to user B.
- [ ] New feature tests (`backend/tests/Feature/SpaAuthTest.php`):
  - Verify `/sanctum/csrf-cookie` sets CSRF cookie.
  - Verify stateful login sets session cookie and returns user data.
  - Verify `/api/v1/me` authenticates via session cookie.
  - Verify stateful logout terminates session.
  - Verify Bearer token authorization continues to work seamlessly on `/api/v1/me` and `/api/v1/tasks`.
- [ ] All 37+ existing tests plus new tests pass with 100% success rate: `php artisan test`.
- [ ] End-to-end benchmark on 1,000,000 tasks MySQL database confirms response time drops from ~3,000ms to < 50ms.

## Verification
- [ ] `php artisan test` outputs 100% passing tests with 0 failures.
- [ ] Direct curl / tinker performance test on `tasks-challenge.test/api/v1/tasks?page=1&per_page=10&sort_by=created_at&sort_order=desc` logs latency < 50ms.

## Dependencies
Blocked by: 01, 02, 03, 04, 05

## Files Likely Touched
- `backend/tests/Feature/TaskCachingTest.php`
- `backend/tests/Feature/SpaAuthTest.php`
- `backend/tests/Feature/TaskApiTest.php`

## Estimated Scope
M (3 files)
