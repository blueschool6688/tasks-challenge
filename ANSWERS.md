# Technical Interview Answers (Câu Trả Lời Phỏng Vấn Kỹ Thuật)

---

## 4.1. Backend & Database Architecture

### 4.1.1. Giải pháp tối ưu hóa endpoint `GET /api/tasks` khi chạm mốc 1,000,000+ records

Khi bảng `tasks` vượt qua 1,000,000 bản ghi, câu truy vấn `GET /api/tasks?status=...&assigned_to=...&search=...` sẽ nhanh chóng trở thành nút thắt cổ chai nếu chỉ dùng các truy vấn SQL mặc định. Dưới đây là 8 giải pháp kỹ thuật tối ưu toàn diện từ tầng Database đến tầng Ứng dụng và Hạ tầng:

#### 1. Đánh chỉ mục hỗn hợp (Composite Indexes) đúng trọng tâm
- **Vấn đề:** Các điều kiện lọc thường đi kèm: `assigned_to`, `status`, `deleted_at`, và `created_at` (sắp xếp). Nếu chỉ đánh chỉ mục đơn lẻ (Single-column Index), MySQL phải thực hiện Index Merge rất tốn tài nguyên.
- **Giải pháp:** Tạo Composite Index theo quy tắc Equality -> Range/Sort:
  ```sql
  -- Tối ưu cho User thông thường (luôn lọc theo assigned_to và soft delete)
  CREATE INDEX idx_tasks_user_status_created 
  ON tasks(assigned_to, status, deleted_at, created_at DESC);

  -- Tối ưu cho Admin (lọc theo status trên toàn bộ hệ thống)
  CREATE INDEX idx_tasks_status_deleted_created 
  ON tasks(status, deleted_at, created_at DESC);
  ```

#### 2. Chuyển đổi từ Offset Pagination (`LIMIT ... OFFSET ...`) sang Keyset / Cursor Pagination
- **Vấn đề:** Khi người dùng mở trang thứ 10,000 (`OFFSET 100000 LIMIT 10`), database buộc phải đọc và loại bỏ 100,000 hàng trước khi lấy 10 hàng kế tiếp -> Tốn I/O đĩa và CPU nghiêm trọng ($O(N)$).
- **Giải pháp:** Sử dụng **Cursor Pagination** của Laravel:
  ```php
  // Thay vì: Task::paginate(15);
  // Sử dụng:
  $tasks = Task::orderBy('id', 'desc')->cursorPaginate(15);
  ```
  Database sẽ thực thi dạng: `WHERE id < :last_seen_id ORDER BY id DESC LIMIT 15`, tận dụng chỉ mục B-Tree với độ phức tạp cố định $O(1)$.

#### 3. Tách Full-Text Search hoặc tích hợp Elasticsearch / Meilisearch (Laravel Scout)
- **Vấn đề:** Câu lệnh `LIKE '%search%'` kích hoạt Full Table Scan vì có ký tự wildcard `%` ở đầu, vô hiệu hóa mọi chỉ mục B-Tree thông thường.
- **Giải pháp:**
  - Cấp độ Database: Dùng MySQL `FULLTEXT` index với hàm `MATCH(title, description) AGAINST(...)`.
  - Cấp độ Chuyên dụng: Tích hợp **Meilisearch** hoặc **Elasticsearch** qua **Laravel Scout**. Khi tạo/sửa task, Laravel đồng bộ dữ liệu vào search engine thông qua Queue. Search engine trả về danh sách `task_id` trong vài milli-giây, sau đó backend chỉ cần truy vấn `WHERE id IN (...)`.

#### 4. Kỹ thuật Deferred Join (Late Row Lookups)
- **Vấn đề:** Khi cần `SELECT *` kèm phân trang truyền thống, database phải đọc toàn bộ các cột (bao gồm `description` kiểu TEXT) cho hàng trăm nghìn bản ghi chỉ để bỏ qua chúng.
- **Giải pháp:** Chỉ join lấy dữ liệu chi tiết sau khi đã xác định được tập ID của trang:
  ```sql
  SELECT t.*, u.name 
  FROM tasks t
  JOIN (
      SELECT id FROM tasks 
      WHERE assigned_to = 2 AND status = 'todo' AND deleted_at IS NULL
      ORDER BY id DESC 
      LIMIT 15 OFFSET 50000
  ) AS sub USING (id)
  JOIN users u ON u.id = t.assigned_to;
  ```

#### 5. Eager Loading có kiểm soát và Tránh N+1 Query
- Luôn chỉ định rõ các cột cần thiết từ quan hệ để giảm dung lượng RAM mà Eloquent hydration tiêu thụ:
  ```php
  Task::with(['assignee:id,name,email,role'])
      ->select(['id', 'title', 'status', 'assigned_to', 'due_date', 'created_at'])
  ```

#### 6. Áp dụng Caching đa tầng (Multi-tier Caching với Redis)
- **Danh sách tổng quan/Dashboard:** Cache các count thống kê (ví dụ: số task `todo`, `in_progress`, `done`) bằng Redis với Tagging:
  ```php
  $counts = Cache::tags(["user:{$userId}:tasks"])->remember('task_counts', 300, function () use ($userId) {
      return Task::where('assigned_to', $userId)->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status');
  });
  ```
- **Invalidation:** Tự động xóa cache của user thông qua Eloquent Model Events (`saved`, `deleted`).

#### 7. Database Partitioning & Read Replicas
- **Read/Write Splitting:** Cấu hình trong `config/database.php` tách riêng kết nối `read` và `write`. Tất cả các câu truy vấn `GET /api/tasks` được phân tải sang 1 hoặc nhiều MySQL Read Replicas.
- **Table Partitioning:** Phân vùng bảng `tasks` theo khoảng thời gian (Range Partitioning theo năm hoặc quý) hoặc theo `status`. Các task đã `done` lâu ngày có thể chuyển sang bảng lưu trữ `tasks_archive` để giữ kích thước bảng hoạt động luôn dưới ngưỡng 1 triệu hàng.

#### 8. Giới hạn tối đa kích thước trang (`per_page`)
- Ngăn chặn client gửi tham số độc hại như `?per_page=1000000` làm tràn bộ nhớ (OOM). Giới hạn cứng trong code:
  ```php
  $perPage = max(1, min(100, (int) $request->query('per_page', 10)));
  ```

---

### 4.1.2. Các giải pháp bảo mật toàn diện cho kiến trúc Laravel API + Vue SPA

Mô hình kiến trúc API + SPA đối mặt với nhiều bề mặt tấn công. Dưới đây là các biện pháp bảo vệ chuyên sâu theo tiêu chuẩn OWASP:

#### 1. Phòng chống SQL Injection (SQLi)
- **Nguyên tắc:** Không bao giờ nối chuỗi biến người dùng vào câu truy vấn raw SQL.
- **Thực thi trong Laravel:**
  - Tận dụng triệt để Eloquent ORM và Query Builder vì chúng mặc định sử dụng PDO Prepared Statements với Parameter Binding.
  - Khi bắt buộc dùng Raw Query, luôn truyền mảng bindings:
    ```php
    // NGUY HIỂM: DB::select("SELECT * FROM tasks WHERE title = '$input'");
    // AN TOÀN:
    DB::select("SELECT * FROM tasks WHERE title = :title", ['title' => $input]);
    ```
  - Cẩn trọng với cột sắp xếp dynamic: Chỉ cho phép sắp xếp theo danh sách whitelist (`in_array($sortBy, ['id', 'title', 'created_at'], true)`).

#### 2. Phòng chống Cross-Site Scripting (XSS)
- **Frontend (Vue 3):**
  - Mặc định Vue 3 tự động encode HTML khi dùng cú pháp mustache `{{ text }}` và `v-bind`.
  - Tuyệt đối không dùng `v-html` với dữ liệu do người dùng nhập hoặc từ API trả về.
  - Sử dụng thư viện sanitize (như DOMPurify) nếu bắt buộc phải render nội dung rich text.
- **Backend (Laravel):**
  - Sử dụng Form Request để validate và strip tags đối với các trường nhạy cảm.
  - Thiết lập header HTTP **Content-Security-Policy (CSP)**:
    ```text
    Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';
    ```

#### 3. Phòng chống Cross-Site Request Forgery (CSRF)
- **Đối với API dùng Bearer Token (Sanctum PlainTextToken):**
  - Bản thân các request gửi token qua header `Authorization: Bearer <token>` đã miễn nhiễm với tấn công CSRF truyền thống, vì trình duyệt không tự động đính kèm header này trong các cross-origin request (khác với Cookie).
- **Nếu chuyển sang cơ chế Cookie-based SPA (Sanctum SPA mode):**
  - Bắt buộc kích hoạt middleware `EnsureFrontendRequestsAreStateful`.
  - Frontend phải gọi `GET /sanctum/csrf-cookie` trước khi đăng nhập để nhận cookie `XSRF-TOKEN`, và Axios tự động gửi lại trong header `X-XSRF-TOKEN`.
  - Cấu hình cookie với cờ `SameSite=Lax` hoặc `Strict`.

#### 4. Chiến lược lưu trữ Token an toàn (Token Storage: LocalStorage vs HttpOnly Cookie)
- **Phân tích trade-off:**
  - `localStorage`: Dễ triển khai, tiện lợi cho mobile/desktop app và decoupling hoàn toàn giữa domain API và frontend. **Nhược điểm:** Dễ bị đánh cắp nếu ứng dụng dính lỗ hổng XSS.
  - `HttpOnly Cookie`: Miễn nhiễm hoàn toàn với việc JavaScript đọc trộm token (chống XSS đánh cắp token). **Nhược điểm:** Phải xử lý CSRF và cấu hình domain/CORS chặt chẽ.
- **Khuyến nghị kiến trúc:**
  - Nếu dùng Token (như đề bài): Lưu token với thời gian hết hạn ngắn (Short-lived Access Token: 15–30 phút) kết hợp Refresh Token.
  - Thiết lập cơ chế thu hồi token ngay lập tức khi đăng xuất (`$request->user()->currentAccessToken()->delete()`).

#### 5. Bắt buộc HTTPS & Cấu hình HTTP Strict Transport Security (HSTS)
- Mã hóa toàn bộ lưu lượng truyền tải trên đường truyền, ngăn chặn Man-in-the-Middle (MITM) và sniffing token.
- Bật middleware ép buộc HTTPS trong Laravel:
  ```php
  if (app()->environment('production')) {
      URL::forceScheme('https');
  }
  ```
- Cấu hình server trả về các security headers:
  ```text
  Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  ```

#### 6. Rate Limiting & Throttling
- Giới hạn tần suất gọi API để chống brute-force mật khẩu và DoS.
- Trong Laravel 11 `routes/api.php`:
  ```php
  Route::middleware(['throttle:api'])->group(...); // Mặc định 60 requests/phút
  Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // Tối đa 5 lần/phút
  ```

#### 7. Cấu hình CORS nghiêm ngặt (Cross-Origin Resource Sharing)
- Trong `config/cors.php`, không để `'allowed_origins' => ['*']` trên production.
- Chỉ whitelist domain chính xác của SPA (ví dụ: `https://app.yourdomain.com`).

---

## 4.2. Frontend Architecture & TypeScript

### 4.2.1. Các lợi ích chính khi sử dụng TypeScript trong Vue 3 SPA

1. **Phát hiện lỗi ngay tại Compile-Time (Early Bug Detection):**
   - JavaScript chỉ phát hiện lỗi khi chạy (Runtime), dẫn đến các lỗi kinh điển như `TypeError: Cannot read properties of undefined (reading 'name')`.
   - TypeScript kiểm tra kiểu dữ liệu tĩnh trong quá trình gõ code và khi build (`vue-tsc`), loại bỏ tới 80% các lỗi logic cơ bản về định dạng dữ liệu API hoặc sai tên thuộc tính.

2. **Cung cấp Trải nghiệm Lập trình Vượt trội (Developer Experience & Autocomplete):**
   - Hỗ trợ IntelliSense mạnh mẽ trong IDE (VS Code, PhpStorm): gợi ý tự động props của component, trường dữ liệu của model `Task`, tham số emit, và phương thức của store.
   - Giảm thiểu việc phải liên tục mở file backend/Postman để tra cứu key JSON của API (`title`, `assigned_to`, `due_date`).

3. **Tự tin Tái cấu trúc mã nguồn (Refactoring with Confidence):**
   - Khi đổi tên một thuộc tính trong Interface (ví dụ từ `assignee_id` sang `assigned_to`), TypeScript Compiler sẽ ngay lập tức báo đỏ tất cả các file component và store đang sử dụng thuộc tính cũ. Bạn không bao giờ lo bỏ sót vị trí cần sửa khi dự án phình to.

4. **Tài liệu hóa sống (Self-Documenting Code):**
   - Các interface trong `src/types/index.ts` đóng vai trò như hợp đồng dữ liệu rõ ràng giữa Frontend và Backend. Bất kỳ lập trình viên mới nào vào dự án chỉ cần đọc Interface là nắm bắt được toàn bộ cấu trúc dữ liệu mà không cần xem tài liệu rời rạc.

5. **Tích hợp hoàn hảo với Vue 3 Composition API:**
   - Vue 3 được viết hoàn toàn bằng TypeScript từ đầu. Các tính năng như `defineProps<{ ... }>()`, `defineEmits<{ ... }>()`, `ref<Task>()`, `computed<boolean>()` hoạt động trơn tru với Type Inference chuẩn xác 100%, không cần runtime validators rườm rà.

---

### 4.2.2. Đoạn mã mẫu Vue 3 Component chuẩn mực (`<script setup lang="ts">`)

Dưới đây là một component mẫu chuẩn sản phẩm: `TaskCard.vue`, thể hiện đầy đủ:
- Strongly-typed `defineProps` với generic syntax và default values (`withDefaults`).
- Strongly-typed `defineEmits`.
- Computed properties có khai báo kiểu trả về tường minh.
- Phù hợp với Vuetify 3 và không sử dụng `any`.

```vue
<template>
  <v-card rounded="lg" elevation="2" class="task-card pa-4 mb-3" :border="isOverdue ? 'error' : undefined">
    <div class="d-flex align-center justify-space-between mb-2">
      <!-- Tiêu đề task -->
      <h3 class="text-subtitle-1 font-weight-bold text-truncate text-high-emphasis" :title="task.title">
        {{ task.title }}
      </h3>

      <!-- Status Chip -->
      <v-chip
        :color="statusColor"
        size="small"
        variant="flat"
        class="font-weight-bold text-uppercase"
      >
        <v-icon start size="12">mdi-circle</v-icon>
        {{ task.status }}
      </v-chip>
    </div>

    <!-- Mô tả task -->
    <p v-if="task.description" class="text-body-2 text-medium-emphasis mb-3 text-truncate">
      {{ task.description }}
    </p>

    <v-divider class="my-3" />

    <div class="d-flex align-center justify-space-between">
      <!-- Thông tin Assignee -->
      <div class="d-flex align-center ga-2">
        <v-avatar size="24" color="primary">
          <span class="text-caption text-white font-weight-bold">
            {{ assigneeInitials }}
          </span>
        </v-avatar>
        <span class="text-caption font-weight-medium text-high-emphasis">
          {{ task.assignee?.name ?? 'Unassigned' }}
        </span>
      </div>

      <!-- Actions -->
      <div class="d-flex align-center ga-1">
        <v-btn
          v-if="canEdit"
          icon="mdi-pencil"
          variant="text"
          density="comfortable"
          size="small"
          color="primary"
          title="Edit Task"
          @click="emit('edit', task)"
        />
        <v-btn
          v-if="canDelete"
          icon="mdi-delete"
          variant="text"
          density="comfortable"
          size="small"
          color="error"
          title="Delete Task"
          @click="emit('delete', task.id)"
        />
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// 1. Định nghĩa Type Interfaces
export type TaskStatus = 'todo' | 'in_progress' | 'done'

export interface TaskAssignee {
  id: number
  name: string
  email: string
  role?: 'admin' | 'user'
}

export interface TaskItem {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  assigned_to: number
  due_date: string | null
  created_at: string
  updated_at: string
  assignee?: TaskAssignee | null
}

// 2. Strongly-typed Props với withDefaults
interface Props {
  task: TaskItem
  canEdit?: boolean
  canDelete?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canEdit: false,
  canDelete: false,
})

// 3. Strongly-typed Emits
interface Emits {
  (e: 'edit', task: TaskItem): void
  (e: 'delete', taskId: number): void
}

const emit = defineEmits<Emits>()

// 4. Computed Properties có type annotation tường minh
const statusColor = computed<string>(() => {
  switch (props.task.status) {
    case 'todo':
      return 'grey-darken-1'
    case 'in_progress':
      return 'blue'
    case 'done':
      return 'green'
    default:
      return 'grey'
  }
})

const assigneeInitials = computed<string>(() => {
  const name = props.task.assignee?.name || 'U'
  return name.slice(0, 2).toUpperCase()
})

const isOverdue = computed<boolean>(() => {
  if (!props.task.due_date || props.task.status === 'done') return false
  const dueDate = new Date(props.task.due_date)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return dueDate < today
})
</script>

<style scoped>
.task-card {
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}
</style>
```
