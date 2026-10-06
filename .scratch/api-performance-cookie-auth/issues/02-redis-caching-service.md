# Issue 02: Redis Caching Service with Key-Versioning Invalidation

Status: resolved
Type: task

## Description
Eliminate the ~1.5s `COUNT(*)` pagination bottleneck and repeat query latency by implementing a Redis caching layer using predis. Invalidation uses atomic version counters (`tasks:version`, `users:version`) instead of cache tags, ensuring instant, race-free invalidation upon writes. Total count and initial page payloads are cached with fail-open fallback.

## Acceptance Criteria
- [ ] `predis/predis` package is installed and configured as the Redis client in Laravel.
- [ ] Model lifecycle events on `Task` (`created`, `updated`, `deleted`, `restored`, `forceDeleted`) atomically increment `tasks:version`.
- [ ] Model lifecycle events on `User` (`saved`, `deleted`) atomically increment `users:version`.
- [ ] `TaskCacheService` manages cache key generation and retrieval:
  - Total count cached at `tasks:v{ver}:count:{scope}:{filterHash}` (TTL: 10 mins).
  - Page payloads (pages 1–3, no search) cached at `tasks:v{ver}:page:{scope}:{filterHash}:{page}:{perPage}:{sort}` (TTL: 60s).
  - Users list cached at `users:v{ver}:list` (TTL: 1h).
- [ ] Scopes isolate tenancy (`all` for admin, `u{id}` for regular users) so regular users can never receive cached admin data.
- [ ] Controller attaches response headers `X-Cache: HIT|MISS` and `Server-Timing` metric.
- [ ] Fail-open: If Redis throws a connection exception, the application logs a warning and falls back gracefully to MySQL.

## Verification
- [ ] `php artisan test --filter=TaskApiTest` passes.
- [ ] Redis cache keys verified in redis-cli / tinker during listing requests.
- [ ] Write operations (POST/PUT/DELETE tasks) bump version and invalidate previous cache keys immediately.

## Dependencies
Blocked by: 01

## Files Likely Touched
- `backend/composer.json`
- `backend/.env` / `backend/.env.example`
- `backend/app/Models/Task.php`
- `backend/app/Models/User.php`
- `backend/app/Services/TaskCacheService.php`
- `backend/app/Http/Controllers/TaskController.php`
- `backend/app/Http/Controllers/UserController.php`

## Estimated Scope
M (4 files)
