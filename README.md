# Team Task Manager (Full-Stack Laravel 11 + Vue 3 SPA)

A clean, production-grade task management web application built with **Laravel 11 REST API** and **Vue 3 SPA (TypeScript + Vuetify 3)**. Designed with strict adherence to Clean Code, SOLID principles, and granular Role-Based Access Control (RBAC).

---

## 🛠 Tech Stack

### Backend
- **Framework:** Laravel 11.x
- **Language:** PHP 8.3 with `declare(strict_types=1);` in all application files
- **Database:** MySQL 8 (default for zero-configuration reviewer evaluation; SQLite 3 ready)
- **Authentication:** Laravel Sanctum (stateless API tokens)
- **API Standards:** JSON REST with standard HTTP status codes (`200`, `201`, `204`, `401`, `403`, `404`, `422`)
- **Testing:** PHPUnit / Pest Feature & Unit test suites

### Frontend
- **Framework:** Vue 3 (Composition API with `<script setup lang="ts">`)
- **Language:** TypeScript 5.7+ (Strict mode, no `any` types)
- **Component Library:** Vuetify 3 (Material Design with MDI icon font)
- **State Management:** Pinia stores (`useAuthStore`, `useTaskStore`)
- **Routing:** Vue Router 4 with navigation guards (`requiresAuth`, `guestOnly`)
- **HTTP Client:** Axios with Bearer token request interceptor and 401 response handler
- **Build Tool:** Vite 6

---

## 🚀 Quick Start Guide

### Prerequisites
- **PHP** >= 8.3 with `pdo_sqlite`, `openssl`, `mbstring` extensions
- **Composer** >= 2.x
- **Node.js** >= 18.x and **npm** >= 9.x
- **Make** (optional, included in Git Bash / Linux / Mac / WSL)

---

### ⚡ Quick Commands (via Makefile)

If you have `make` installed (available in Git Bash, Laragon, or Linux/macOS), you can manage the whole project with one-liners from the root directory:

```bash
make help          # Show all available commands
make setup         # One-step complete setup (install dependencies + migrate + seed)
make dev           # Instructions for running dev servers
make dev-backend   # Start Laravel API server on http://localhost:8000
make dev-frontend  # Start Vue 3 Vite dev server on http://localhost:5173
make test          # Run backend tests + frontend type check
make format        # Automatically format code with Laravel Pint
make build         # Build frontend SPA & optimize backend for production
make clean         # Clear caches and build artifacts
```

### 1. Backend Setup

```bash
# Navigate to the backend directory
cd backend

# Install PHP dependencies (if not already installed)
composer install

# Environment setup
# (Note: .env is pre-configured for SQLite)
cp .env.example .env
php artisan key:generate

# Run database migrations and seed test data
php artisan migrate:fresh --seed

# (Optional) Run automated backend test suite
php artisan test

# Start the Laravel local API development server
php artisan serve
```
Backend API will be accessible at: **`http://localhost:8000`**
Interactive Swagger / OpenAPI Docs: **`http://localhost:8000/docs`**

---

### 2. Frontend Setup

```bash
# Open a new terminal and navigate to the frontend directory
cd frontend

# Install Node dependencies
npm install

# Start the Vite development server
npm run dev
```
Frontend application will be accessible at: **`http://localhost:5173`**

---

## 🔐 Demo / Evaluation Accounts

The database seeder automatically initializes the following test accounts with password: **`password`**:

| Name | Email | Password | Role | Permissions |
| :--- | :--- | :--- | :--- | :--- |
| **Admin User** | `admin@example.com` | `password` | `admin` | View all team tasks, assign tasks to any user, edit and soft-delete any task. |
| **John Doe** | `john@example.com` | `password` | `user` | View only own assigned tasks, create tasks for self, edit & delete only own tasks. |
| **Jane Smith** | `jane@example.com` | `password` | `user` | View only own assigned tasks, create tasks for self, edit & delete only own tasks. |

> **Pro-tip:** On the `/login` page, you can click on the quick-fill chips under the login card to instantly populate credentials for any of the above accounts!

---

## 📋 API Endpoints Reference

| Method | Endpoint | Auth Required | Description |
| :--- | :--- | :---: | :--- |
| `POST` | `/api/login` | No | Authenticate user, returns Sanctum PlainTextToken + user details |
| `POST` | `/api/logout` | Yes | Revoke current access token (returns `204`) |
| `GET` | `/api/me` | Yes | Get currently authenticated user details |
| `GET` | `/api/users` | Yes | Get list of users (`id`, `name`, `email`, `role`) for task assignment |
| `GET` | `/api/tasks` | Yes | List tasks with filters (`?status=`, `?assigned_to=`, `?search=`, pagination `?page=&per_page=`) |
| `POST` | `/api/tasks` | Yes | Create a task (Admin can assign to any; User can only assign to self) |
| `GET` | `/api/tasks/{task}` | Yes | View single task (Admin any; User own task) |
| `PUT` | `/api/tasks/{task}` | Yes | Update task (Admin any; User own task, cannot reassign) |
| `DELETE`| `/api/tasks/{task}` | Yes | Soft-delete task (Admin any; User own task) |

---

## 🛡 Granular Authorization & Scoping

- **`GET /api/tasks`:**
  - **Admin:** Receives all tasks across the company. Can filter by any specific user using `?assigned_to={id}`.
  - **Regular User:** Query is strictly scoped at database level to `WHERE assigned_to = auth()->id()`. Query string overrides like `?assigned_to={other_id}` are strictly ignored.
- **`POST /api/tasks`:**
  - Validated with `StoreTaskRequest`.
  - Inline authorization verifies that non-admin users cannot pass an `assigned_to` ID other than their own ID (triggers HTTP `403 Forbidden`).
- **`PUT /api/tasks/{id}`:**
  - Validated with `UpdateTaskRequest`.
  - Non-admin users cannot edit tasks assigned to others, nor can they reassign their own tasks to another user (triggers HTTP `403 Forbidden`).
- **`DELETE /api/tasks/{id}`:**
  - Non-admin users attempting to delete tasks assigned to another team member receive HTTP `403 Forbidden`.
  - Uses Laravel's `SoftDeletes` trait, preserving audit trail in `deleted_at`.

---

## 💡 Key Design Decisions & Assumptions

1. **Zero-Configuration Review Experience:**
   - SQLite was selected for local development so reviewers can clone, migrate, seed, and test without configuring a MySQL server daemon.
   - For production, swapping to MySQL 8 is seamless by updating `.env` parameters (`DB_CONNECTION=mysql`).
2. **Stateless Bearer Tokens:**
   - Sanctum `PlainTextToken` was chosen to cleanly decouple the SPA frontend from the Laravel API, allowing independent deployment (e.g. Vercel/Netlify for frontend, AWS/Fly.io for API).
3. **Optimized Form Requests with Inline Authorization:**
   - Authorization rules are encapsulated in `StoreTaskRequest` and `UpdateTaskRequest`, keeping controller actions lightweight, predictable, and single-purpose.
4. **Debounced Server-Side Search:**
   - Frontend text search applies a 350ms debounce before dispatching requests, minimizing backend query load.
5. **No `any` Policy in TypeScript:**
   - All state, API responses, payload objects, and event parameters are explicitly typed in `src/types/index.ts`.
