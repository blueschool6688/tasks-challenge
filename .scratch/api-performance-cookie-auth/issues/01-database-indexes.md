# Issue 01: Composite Database Indexes and Query Sorting Hardening

Status: resolved
Type: task

## Description
Eliminate the ~1.5s data query bottleneck on 1,000,000 tasks by introducing targeted composite B-tree indexes that match the leftmost prefix of default and filtered queries. Harden `TaskController` ordering so only indexed paths can be sorted, appending `id` as a deterministic tiebreaker and enforcing a 100-item page limit.

## Acceptance Criteria
- [ ] New migration adds composite index `(deleted_at, created_at)` (`idx_tasks_deleted_created`) for top-level admin listing.
- [ ] Migration adds composite index `(assigned_to, deleted_at, created_at)` (`idx_tasks_assigned_deleted_created`) for regular user task listing without status filter.
- [ ] Migration adds composite index `(deleted_at, due_date)` (`idx_tasks_deleted_due_date`) for overdue and due date sorting.
- [ ] `TaskController@index` restricts `$allowedSorts` to `['id', 'created_at', 'due_date', 'status']`. Any other field safely falls back to `created_at desc`.
- [ ] Every query orders with `id asc/desc` as tiebreaker to guarantee deterministic pagination.
- [ ] MySQL `EXPLAIN` on `WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 10` confirms index seek with `rows <= 10` (no filesort, no range scan across 483k rows).

## Verification
- [ ] Run migration: `php artisan migrate` runs cleanly.
- [ ] Existing backend test suite passes: `php artisan test --filter=TaskApiTest`.
- [ ] Explain plan verified via tinker / mysql CLI showing `idx_tasks_deleted_created` utilized.

## Dependencies
None

## Files Likely Touched
- `backend/database/migrations/2026_10_06_080000_add_pagination_composite_indexes_to_tasks_table.php`
- `backend/app/Http/Controllers/TaskController.php`

## Estimated Scope
S (2 files)
