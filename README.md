# Team Task Manager (Full-Stack Laravel 11 + Vue 3 SPA)

A production-ready, enterprise-grade task management web application built with **Laravel 11 REST API** and **Vue 3 SPA (TypeScript + Vuetify 3)**. Architected with strict adherence to Clean Architecture, Deep Module principles, Granular Role-Based Access Control (RBAC), and high-performance database indexing.

---

## 📑 Table of Contents

- [Overview & Architecture](#-overview--architecture)
- [Tech Stack](#-tech-stack)
- [Development Environment Setup & Run Guide](#-development-environment-setup--run-guide)
- [Production Deployment & Build Guide](#-production-deployment--build-guide)
- [Demo Accounts](#-demo-accounts)
- [RESTful API v1 Specification & Response Schemas](#-restful-api-v1-specification--response-schemas)
- [Database & Query Performance Optimization](#-database--query-performance-optimization)
- [Security & Hardening Architecture](#-security--hardening-architecture)
- [Frontend Clean Architecture & Tree-Shaking](#-frontend-clean-architecture--tree-shaking)
- [Automated Test Suite & Verification](#-automated-test-suite--verification)

---

## 🏛 Overview & Architecture

The repository is structured as a clean monorepo with strict separation of concerns:

```
tasks-challenge/
├── backend/                  # Laravel 11 API (PHP 8.3, Sanctum, SQLite/MySQL)
│   ├── app/
│   │   ├── Http/Controllers/ # TaskController, AuthController, UserController
│   │   ├── Http/Requests/    # StoreTaskRequest, UpdateTaskRequest, LoginRequest
│   │   ├── Http/Resources/   # TaskResource, UserResource (JSON:API Envelope)
│   │   ├── Models/           # Task (SoftDeletes), User (HasApiTokens)
│   │   └── Policies/         # TaskPolicy (Granular RBAC authorization)
│   ├── database/
│   │   ├── migrations/       # Schema + Composite Performance Indexes
│   │   └── seeders/          # Idempotent DatabaseSeeder (3 users, 12 tasks)
│   ├── routes/api.php        # Versioned routes (/api/v1/...) with aliases
│   └── tests/Feature/        # 37 Automated feature tests (112 assertions)
├── frontend/                 # Vue 3 SPA (TypeScript, Vuetify 3, Pinia, Vite)
│   ├── src/
│   │   ├── api/              # Transport (axios.ts) & Adapters (tasks.api, auth.api)
│   │   ├── components/       # AppHeader.vue, TaskForm.vue
│   │   ├── pages/            # LoginPage.vue, TasksPage.vue
│   │   ├── stores/           # Pinia stores (auth.ts, tasks.ts)
│   │   ├── types/            # Strict TypeScript interfaces (Zero `any`)
│   │   └── utils/            # Domain helpers (task.ts, errors.ts)
│   └── vite.config.ts        # Optimized with on-demand tree-shaking
├── docs/                     # Architecture Decision Records (ADRs) & Specs
│   └── adr/                  # ADR 0001: Frontend Clean Architecture
├── tasks/                    # Project specification, plan, and evaluation checklist
├── Makefile                  # Cross-platform orchestration (Bash & Windows CMD)
├── package.json              # Root concurrent task runner
└── ANSWERS.md                # In-depth architectural & scaling technical review
```

---

## 🛠 Tech Stack

### Backend
- **Framework:** Laravel 11.x
- **Language:** PHP 8.3 with `declare(strict_types=1);` in 100% of files
- **Database:** SQLite for zero-config evaluation (tested and isolated in memory) / MySQL 8 ready
- **Authentication:** Laravel Sanctum (stateless OAuth2 Bearer Tokens)
- **API Architecture:** RESTful API with URI versioning (`/api/v1/...`)
- **Interactive Documentation:** Swagger UI / OpenAPI 3.0.3 at `http://localhost:8000/docs`
- **Testing:** PHPUnit with isolated in-memory database configuration

### Frontend
- **Framework:** Vue 3.5+ (Composition API with `<script setup lang="ts">`)
- **Language:** TypeScript 5.7+ (Strict mode, zero `any`)
- **Component System:** Vuetify 3 (Material Design with MDI icon set)
- **State Management:** Pinia (Deep Module pattern)
- **Routing:** Vue Router 4 with navigation guards
- **HTTP Client:** Axios with Sanctum Bearer token injector and 401 interception
- **Bundler:** Vite 6 with `vite-plugin-vuetify` tree-shaking

---

## ⚡ Workflows & Development Lifecycle

The repository includes a comprehensive, cross-platform [`Makefile`](file:///c:/laragon/www/tasks-challenge/Makefile) compatible with Windows CMD, PowerShell, Git Bash, MSYS2, Linux, and macOS:

| Command | Action / Workflow |
|---|---|
| `make dev` *(or `npm run dev`)* | Concurrently boots **both** Backend API (`:8000`) and Frontend SPA (`:5173`) in a unified terminal with color-coded tags and unified shutdown. |
| `make setup` | One-step automated setup: installs Composer & NPM packages, generates `.env` and application key, migrates and seeds the database. |
| `make test` | Executes both the Laravel PHPUnit test suite and the Frontend TypeScript type-checking suite. |
| `make test-backend` | Runs all 37 backend tests in an isolated in-memory database (preserving local database seed data). |
| `make test-frontend` | Runs `vue-tsc -b` strict type check across the frontend. |
| `make format` | Automatically formats backend code to PSR-12 / Laravel standards using Laravel Pint. |
| `make build` | Builds the production-ready frontend bundle into `frontend/dist/` and caches Laravel configurations. |
| `make clean` | Purges build artifacts, application cache, route cache, and view cache. |

---

## 💻 Development Environment Setup & Run Guide

### Prerequisites
- **PHP** >= 8.3 (with `pdo_sqlite`, `openssl`, `mbstring`, `fileinfo`)
- **Composer** >= 2.x
- **Node.js** >= 18.x and **npm** >= 9.x

---

### Step 1: Initial Setup (Dependencies + Database)

#### Cách 1: Tự động 1 bước qua Makefile (Khuyến nghị)
```bash
make setup
```
Lệnh này sẽ tự động:
1. Cài đặt các gói PHP (`composer install` trong `backend/`)
2. Cài đặt các gói Node (`npm install` trong `frontend/`)
3. Tạo file `backend/.env` từ `.env.example` và generate application key
4. Chạy migration tạo bảng SQLite kèm composite performance indexes và nạp dữ liệu mẫu (Seeder: 3 users, 12 tasks)

#### Cách 2: Thiết lập thủ công từng phần
```bash
# 1. Cài đặt Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
cd ..

# 2. Cài đặt Frontend
cd frontend
npm install
cd ..
```

---

### Step 2: Khởi chạy môi trường Development

Có 3 lựa chọn để khởi chạy môi trường dev tùy theo thói quen và môi trường máy của bạn:

#### Lựa chọn A: Chạy đồng thời 2 service trong 1 Terminal duy nhất (Tiện lợi nhất)
```bash
make dev
# hoặc chạy qua npm:
npm run dev
```
Hệ thống sẽ chạy song song:
- **Backend API:** `http://localhost:8000` (hiển thị tag `[BACKEND]` màu xanh dương)
- **Frontend SPA:** `http://localhost:5173` (hiển thị tag `[FRONTEND]` màu xanh lá)
- Nhấn `Ctrl + C` để dừng đồng thời cả 2 service an toàn.

#### Lựa chọn B: Mở 2 Tab Terminal độc lập
- **Terminal 1 (Backend API):**
  ```bash
  cd backend
  php artisan serve --host=127.0.0.1 --port=8000
  ```
  *(Truy cập Swagger Docs tại: `http://localhost:8000/docs`)*

- **Terminal 2 (Frontend SPA):**
  ```bash
  cd frontend
  npm run dev
  ```
  *(Truy cập ứng dụng tại: `http://localhost:5173`)*

#### Lựa chọn C: Chạy qua Virtual Host của Laragon
Nếu bạn sử dụng Laragon với Apache/Nginx:
1. Laragon tự động ánh xạ host ảo: `http://tasks-challenge.test` trỏ vào `backend/public`.
2. Tạo file `frontend/.env` (nếu chưa có):
   ```env
   VITE_API_URL=http://tasks-challenge.test/api/v1
   ```
3. Chạy frontend dev server:
   ```bash
   cd frontend && npm run dev
   ```

---

## 🚀 Production Deployment & Build Guide

### Step 1: Build Production Frontend SPA
Frontend được đóng gói thành các file tĩnh HTML/CSS/JS được tối ưu hóa tối đa, bẻ nhỏ chunk và bật tree-shaking:

```bash
# Cách 1: Dùng Makefile
make build-frontend

# Cách 2: Chạy trực tiếp qua NPM
cd frontend
npm run build
```
Toàn bộ mã nguồn đã build sẽ nằm tại thư mục: **`frontend/dist/`** (chỉ ~321 kB bundle chính).

Để xem trước (preview) bản build production trên cổng 4173:
```bash
make preview
# hoặc: cd frontend && npm run preview
```

---

### Step 2: Tối ưu hóa Backend Laravel cho Production

Khi deploy lên môi trường Production thực tế (Server Linux/Ubuntu, Docker, hoặc Cloud VPS):

```bash
cd backend

# 1. Cài đặt Composer không kèm dev dependencies & tối ưu autoload
composer install --no-dev --optimize-autoloader

# 2. Cấu hình .env Production
# Đổi APP_ENV=production, APP_DEBUG=false, và cấu hình MySQL / PostgreSQL nếu dùng
# Đảm bảo cấu hình CORS: FRONTEND_URL=https://your-frontend-domain.com

# 3. Chạy migration sản xuất
php artisan migrate --force

# 4. Cache toàn bộ cấu hình, routes, và events để đạt hiệu năng tối đa
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

*(Hoặc dùng lệnh ngắn gọn từ root: `make build-backend`)*.

---

### Step 3: Cấu hình Web Server Phục vụ Production (Nginx)

Dưới đây là file cấu hình mẫu chuẩn `nginx.conf` phục vụ cả Frontend SPA (Static Files) và Backend Laravel API trên cùng 1 domain:

```nginx
server {
    listen 80;
    server_name tasks.yourdomain.com;
    root /var/www/tasks-challenge/frontend/dist;
    index index.html;

    # Gzip Compression tối ưu tốc độ tải
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # 1. Frontend SPA: chuyển hướng tất cả route về index.html để Vue Router xử lý
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 2. Backend API: chuyển tiếp các request /api sang Laravel backend/public
    location ^~ /api {
        root /var/www/tasks-challenge/backend/public;
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/tasks-challenge/backend/public/index.php;
        }
    }

    # 3. Swagger Docs UI
    location ^~ /docs {
        root /var/www/tasks-challenge/backend/public;
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/tasks-challenge/backend/public/index.php;
        }
    }

    location ~ /\.ht {
        deny all;
    }
}
```

---

## 🔐 Demo Accounts

The database seeder initializes 3 test users with password: **`password`**:

| Account | Email | Password | Role | Permissions |
|---|---|---|---|---|
| **Admin User** | `admin@example.com` | `password` | `admin` | Full system access: oversee all tasks, assign tasks to any team member, edit or soft-delete any task. |
| **John Doe** | `john@example.com` | `password` | `user` | Scoped view: can only see, create, edit, and soft-delete tasks assigned to himself. |
| **Jane Smith** | `jane@example.com` | `password` | `user` | Scoped view: can only see, create, edit, and soft-delete tasks assigned to herself. |

> **Tip:** The `/login` page includes quick-login chips to auto-fill credentials with a single click!

---

## 📋 RESTful API v1 Specification & Response Schemas

All endpoints follow **RESTful URI Versioning** (`/api/v1/...`). Backward-compatible aliases (`/api/...`) are preserved to prevent breaking legacy consumers.

### Endpoints Table

| Method | URI | Auth | Status Code | Description |
|:---:|---|:---:|:---:|---|
| `POST` | `/api/v1/login` | No | `200 OK` / `422` | Authenticate user; returns Sanctum plainTextToken |
| `POST` | `/api/v1/logout` | Yes | `204 No Content` | Revoke current access token |
| `GET` | `/api/v1/me` | Yes | `200 OK` | Get current authenticated user profile |
| `GET` | `/api/v1/users` | Yes | `200 OK` | Get directory of team members for task assignment |
| `GET` | `/api/v1/tasks` | Yes | `200 OK` | Paginated task list (filters: `status`, `assigned_to`, `search`, `sort_by`, `sort_order`, `page`, `per_page`) |
| `POST` | `/api/v1/tasks` | Yes | `201 Created` / `403` / `422` | Create task (Admin: any assignee; User: self only) |
| `GET` | `/api/v1/tasks/{id}` | Yes | `200 OK` / `403` / `404` | Get single task details with assignee |
| `PUT` | `/api/v1/tasks/{id}` | Yes | `200 OK` / `403` / `422` | Update task fields (Admin: any; User: self only) |
| `DELETE` | `/api/v1/tasks/{id}` | Yes | `204 No Content` / `403` / `404` | Soft-delete task (Admin: any; User: self only) |

---

### Request & Response Examples

#### 1. Authentication (`POST /api/v1/login`)
- **Request Body:**
  ```json
  {
    "email": "admin@example.com",
    "password": "password"
  }
  ```
- **Response `200 OK`:**
  ```json
  {
    "token": "1|lpmGUIja2MIYeIgpUn0Gnfp3n5BZzVtHeb9TYhPkd7cca30d",
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
  ```

#### 2. Task Listing (`GET /api/v1/tasks?status=in_progress&page=1&per_page=10`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`
- **Response `200 OK`:**
  ```json
  {
    "data": [
      {
        "id": 2,
        "title": "Set up project repository",
        "description": "Initialize Git repository with README, .gitignore, and branch protection rules.",
        "status": "in_progress",
        "assigned_to": 1,
        "due_date": "2026-10-05",
        "created_at": "2026-09-30T11:29:51.000000Z",
        "updated_at": "2026-09-30T11:29:51.000000Z",
        "assignee": {
          "id": 1,
          "name": "Admin User",
          "email": "admin@example.com",
          "role": "admin"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 2,
      "per_page": 10,
      "to": 10,
      "total": 12
    }
  }
  ```

#### 3. Task Creation (`POST /api/v1/tasks`)
- **Request Body:**
  ```json
  {
    "title": "Implement automated CI/CD pipeline",
    "description": "Configure GitHub Actions workflow for automated test execution.",
    "status": "todo",
    "assigned_to": 2,
    "due_date": "2026-10-15"
  }
  ```
- **Response `201 Created`:**
  ```json
  {
    "data": {
      "id": 13,
      "title": "Implement automated CI/CD pipeline",
      "description": "Configure GitHub Actions workflow for automated test execution.",
      "status": "todo",
      "assigned_to": 2,
      "due_date": "2026-10-15",
      "created_at": "2026-09-30T13:30:00.000000Z",
      "updated_at": "2026-09-30T13:30:00.000000Z",
      "assignee": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user"
      }
    }
  }
  ```

#### 4. Validation Error (`422 Unprocessable Content`)
```json
{
  "message": "The title field is required. (and 1 more error)",
  "errors": {
    "title": ["The title field is required."],
    "status": ["The selected status is invalid."]
  }
}
```

#### 5. Authorization Error (`403 Forbidden`)
```json
{
  "message": "You can only assign tasks to yourself."
}
```

---

## ⚡ Database & Query Performance Optimization

### 1. Composite Database Indexes
To scale the `tasks` table beyond **1,000,000+ records**, a dedicated migration ([`2026_09_30_140000_add_performance_indexes_to_tasks_table.php`](file:///c:/laragon/www/tasks-challenge/backend/database/migrations/2026_09_30_140000_add_performance_indexes_to_tasks_table.php)) implements composite B-Tree indexes:

```php
Schema::table('tasks', function (Blueprint $table) {
    // 1. Regular user scoped queries (Equality -> SoftDelete -> Sort):
    // WHERE assigned_to = ? AND status = ? AND deleted_at IS NULL ORDER BY created_at DESC
    $table->index(['assigned_to', 'status', 'deleted_at', 'created_at'], 'idx_tasks_user_filter');

    // 2. Admin team-wide status filter queries:
    // WHERE status = ? AND deleted_at IS NULL ORDER BY created_at DESC
    $table->index(['status', 'deleted_at', 'created_at'], 'idx_tasks_status_filter');

    // 3. Due date ordering & overdue detection:
    $table->index(['due_date'], 'idx_tasks_due_date');
});
```

### 2. Query Optimization in `TaskController::index`
- **Selective Column Hydration:** Instead of `SELECT *`, only specific columns are queried:
  ```php
  $query = Task::query()
      ->select(['id', 'title', 'description', 'status', 'assigned_to', 'due_date', 'created_at', 'updated_at'])
      ->with('assignee:id,name,email,role');
  ```
- **Guarded Eager Loading:** `with('assignee:id,name,email,role')` excludes sensitive fields (`password`, `remember_token`) and prevents redundant memory bloat during collection hydration.
- **Page Size Clamping:** Protects server against Out-Of-Memory (OOM) attacks:
  ```php
  $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
  ```

### 3. Blueprint for Scaling to 1,000,000+ Tasks
1. **Keyset / Cursor Pagination:** Transition from `OFFSET ... LIMIT` to `$tasks = Task::orderBy('id', 'desc')->cursorPaginate(15);`, ensuring constant $O(1)$ query time regardless of page depth.
2. **Dedicated Search Engine:** Replace wildcard `LIKE '%...%'` with **Laravel Scout + Meilisearch / Elasticsearch**, keeping index lookups sub-millisecond.
3. **Deferred Joins (Late Row Lookups):** Subquery the IDs first, then join to fetch large `description` text fields only for the final 15 rows.
4. **Multi-tier Redis Caching:** Cache status aggregate counts by user tag (`Cache::tags(["user:{$id}:tasks"])`) with automatic cache busting on Eloquent model lifecycle events.
5. **Read Replicas & Archival Partitioning:** Route read queries to MySQL replicas and partition completed tasks older than 12 months into a `tasks_archive` table.

---

## 🛡 Security & Hardening Architecture

1. **SQL Injection Prevention:**
   - 100% of queries use Eloquent ORM with PDO parameterized bindings.
   - Dynamic sorting enforces strict whitelist validation:
     `$allowedSorts = ['id', 'title', 'status', 'due_date', 'created_at', 'updated_at'];`.
2. **Cross-Site Scripting (XSS) Prevention:**
   - Vue 3 automatically HTML-encodes template interpolations (`{{ }}`).
   - Form Requests sanitize and validate all string inputs.
3. **Stateless Token Security:**
   - High-entropy tokens generated via Laravel Sanctum.
   - Immediate server-side token revocation on logout (`$user->currentAccessToken()->delete()`).
   - Axios interceptor purges `localStorage` and routes to login on HTTP `401`.
4. **CORS Hardening:**
   - Whitelists exact client origins (`http://localhost:5173`, `http://tasks-challenge.test`).
   - Sets `'supports_credentials' => true`, eliminating wildcard `*` preflight rejections.
5. **Granular Authorization (Zero Tampering):**
   - [`TaskPolicy.php`](file:///c:/laragon/www/tasks-challenge/backend/app/Policies/TaskPolicy.php) and Form Requests enforce inline authorization gates: regular users cannot view, edit, reassign, or delete tasks belonging to other team members.

---

## 🎨 Frontend Clean Architecture & Tree-Shaking

The frontend SPA adheres to Clean Architecture and Deep Module design:

```
Vue Component (Presentation)
      │
      ▼
Pinia Store (Application State / Orchestration)
      │
      ▼
Domain API Adapter (tasks.api.ts / auth.api.ts)
      │
      ▼
Transport Client (axios.ts with Bearer token interceptor)
```

### Tree-Shaking & Performance Benchmark
By eliminating wildcard component imports in favor of `vite-plugin-vuetify` on-demand auto-imports:
- **Modules Transformed:** Reduced from **645** to **364** (-43%).
- **Bundle Size:** Main JavaScript chunk reduced from **726.62 kB** to **321.74 kB** (over **55% reduction**, eliminating all Vite chunk warnings).
- **Production Build Speed:** Cut from **4.46s** to **2.04s** (>2x faster).

---

## 🧪 Automated Test Suite & Verification

The backend includes a comprehensive PHPUnit test suite configured with an in-memory SQLite database (`:memory:`) in [`phpunit.xml`](file:///c:/laragon/www/tasks-challenge/backend/phpunit.xml), ensuring that running tests never overwrites or wipes the local seeded database:

```bash
# Run backend test suite
php artisan test
```

### Test Coverage Summary: **37 Tests / 112 Assertions (100% PASS)**
- `AuthApiTest`: Valid credentials, bad password, validation failures, logout revocation, `/api/me`.
- `ApiVersioningTest`: Verification of `/api/v1/login`, `/api/v1/tasks`, `/api/v1/me`, `/api/v1/users`.
- `TaskApiTest`: Admin global view, regular user isolation, status filtering, title searching, inline authorization gates, forbidden mutations (HTTP 403), soft-deletion verification, sorting, and pagination boundaries.

```bash
# Run frontend TypeScript strict type-checking
cd frontend && npm run type-check
```
*Zero errors, strict null checks enabled, zero `any`.*
