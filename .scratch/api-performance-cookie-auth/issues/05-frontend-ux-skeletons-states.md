# Issue 05: Frontend UX States and Polished Asynchronous Feedback

Status: resolved
Type: task

## Description
Apply anti-slop frontend discipline (design-taste-frontend) to the application's loading, empty, and authentication lifecycle states. Replace jarring blank screen flashes with smooth table skeletons during task loading, disable forms with subtle feedback during submission, and provide clear user feedback on session expiration.

## Design Read & Dials
- Design Read: B2B productivity application for team task coordination, with a clean restrained aesthetic, leaning toward Vuetify 3 design system with polished asynchronous states.
- `DESIGN_VARIANCE: 5` (Clean, structured alignment)
- `MOTION_INTENSITY: 3` (Subtle, non-distracting CSS transitions)
- `VISUAL_DENSITY: 5` (Standard B2B productivity data density)

## Acceptance Criteria
- [ ] `TasksPage.vue` displays table skeleton placeholder rows (`v-skeleton-loader` or styled shimmer rows matching table columns) instead of an empty white space or single spinner during data loading.
- [ ] Table skeleton matches the exact layout of Title, Assignee, Status, Due Date, and Actions columns to eliminate Cumulative Layout Shift (CLS).
- [ ] `LoginPage.vue` properly disables submit button and inputs while authentication request is pending, showing an inline loader inside the button.
- [ ] On session expiration (401 response while browsing), an informative snackbar or toast notifies the user ("Your session has expired. Please sign in again.") rather than a silent kick to login.
- [ ] Retain all existing Vuetify styling and strict TypeScript types (zero `any`).

## Verification
- [ ] `npm run build` succeeds without errors.
- [ ] Manual inspection of slow network throttle (Fast 3G in DevTools) confirms skeleton table rows appear smoothly before data renders.
- [ ] Form submission visibly locks and displays spinner without allowing duplicate clicks.

## Dependencies
Blocked by: 04

## Files Likely Touched
- `frontend/src/pages/TasksPage.vue`
- `frontend/src/pages/LoginPage.vue`
- `frontend/src/App.vue`

## Estimated Scope
S (3 files)
