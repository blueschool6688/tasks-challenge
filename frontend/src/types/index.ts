export type UserRole = 'admin' | 'user'

export type TaskStatus = 'todo' | 'in_progress' | 'done'

export type TaskSortField = 'id' | 'title' | 'status' | 'due_date' | 'created_at' | 'updated_at'

export type SortOrder = 'asc' | 'desc'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
}

export interface TaskAssignee {
  id: number
  name: string
  email: string
  role?: UserRole
}

export interface Task {
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

export interface TaskFormPayload {
  title: string
  description?: string | null
  status: TaskStatus
  assigned_to: number
  due_date?: string | null
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthResponse {
  token: string
  user: User
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
}

export interface TaskFilterParams {
  status?: TaskStatus | ''
  assigned_to?: number | ''
  search?: string
  page?: number
  per_page?: number
  sort_by?: TaskSortField
  sort_order?: SortOrder
}
