# Issue 04: Frontend Cookie-Based Authentication with Pinia State

Status: resolved
Type: task

## Description
Migrate the Vue 3 SPA away from `localStorage` token storage. Pinia becomes the single source of truth for auth state in memory. Axios is configured with `withCredentials: true` and `withXSRFToken: true`, communicating via Vite proxy and HttpOnly session cookies. Route guards properly wait for session hydration on app startup.

## Acceptance Criteria
- [ ] Vite dev configuration (`frontend/vite.config.ts`) proxies `/api` and `/sanctum` requests to backend URL to ensure same-origin cookie transmission in local dev.
- [ ] Axios client (`frontend/src/api/axios.ts`) configured with `withCredentials: true` and `withXSRFToken: true`.
- [ ] All `localStorage.getItem('token')`, `localStorage.setItem('token')`, and `localStorage.removeItem('token')` occurrences are completely eliminated.
- [ ] Request interceptor no longer attaches `Authorization: Bearer` header.
- [ ] Response interceptor handles 419 (CSRF token expiration) by requesting `/sanctum/csrf-cookie` and retrying once.
- [ ] Response interceptor handles 401 by resetting Pinia auth state and navigating via Vue Router without hard reload.
- [ ] Pinia `useAuthStore` manages `user: ref<User | null>`, `status`, and `isAdmin` purely in memory.
- [ ] `useAuthStore.init()` fetches `GET /api/v1/me` on initial application boot to hydrate user state from session cookie.
- [ ] Vue Router navigation guard (`frontend/src/router/index.ts`) awaits `authStore.init()` before resolving protected routes, preventing flash of login screen on page refresh.

## Verification
- [ ] `npm run build` in `frontend/` succeeds without TypeScript or Vite errors.
- [ ] Login, page refresh, and logout work seamlessly without any tokens visible in Application > Local Storage.
- [ ] Session cookie (`tasks_challenge_session`) visible in Application > Cookies.

## Dependencies
Blocked by: 03

## Files Likely Touched
- `frontend/vite.config.ts`
- `frontend/src/api/axios.ts`
- `frontend/src/api/auth.api.ts`
- `frontend/src/stores/auth.ts`
- `frontend/src/router/index.ts`
- `frontend/src/App.vue`

## Estimated Scope
M (5 files)
