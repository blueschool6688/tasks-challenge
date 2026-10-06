# Issue 03: Sanctum Cookie-Based SPA Authentication Backend

Status: resolved
Type: task

## Description
Migrate the Laravel backend to support first-party SPA stateful authentication via HttpOnly cookies and CSRF protection (`XSRF-TOKEN`), removing reliance on `localStorage` tokens for web clients while preserving Bearer token support for automated tests and non-browser clients (Postman/mobile).

## Acceptance Criteria
- [ ] Laravel 11 `bootstrap/app.php` enables Sanctum stateful API middleware (`statefulApi()`).
- [ ] `config/sanctum.php` includes SPA domains (`localhost:5173`, `127.0.0.1:5173`, `tasks-challenge.test`) in `stateful`.
- [ ] `config/cors.php` sets `supports_credentials => true` with explicit allowed origins; wildcard `*` is strictly avoided.
- [ ] `config/session.php` enforces `http_only => true`, `same_site => 'lax'`.
- [ ] `AuthController@login` authenticates SPA users via `Auth::attempt()`, regenerates the session, and sets the HttpOnly session cookie without returning a token in the response for stateful requests.
- [ ] Non-stateful clients (Postman, automated tests) requesting JSON token auth continue to receive a Sanctum PersonalAccessToken (dual-mode or `/api/v1/tokens` endpoint) so existing test suites and external integrations remain 100% operational.
- [ ] `AuthController@logout` invalidates session, regenerates CSRF token, and deletes personal access token if present.
- [ ] Route `/sanctum/csrf-cookie` is fully operational and CORS-accessible to the SPA.

## Verification
- [ ] `php artisan test --filter=AuthApiTest` passes.
- [ ] Session cookie (`tasks_challenge_session` or `laravel_session`) and `XSRF-TOKEN` cookie inspected and verified via curl / browser DevTools.
- [ ] Authenticated requests to `/api/v1/me` and `/api/v1/tasks` succeed using only cookies without any `Authorization: Bearer` header.

## Dependencies
None

## Files Likely Touched
- `backend/bootstrap/app.php`
- `backend/config/sanctum.php`
- `backend/config/cors.php`
- `backend/config/session.php`
- `backend/app/Http/Controllers/AuthController.php`
- `backend/routes/api.php`

## Estimated Scope
M (4 files)
