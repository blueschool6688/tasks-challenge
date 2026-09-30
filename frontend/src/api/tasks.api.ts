import apiClient from './axios'
import type {
  Task,
  TaskFilterParams,
  TaskFormPayload,
  PaginatedResponse,
} from '@/types'

/**
 * Task Data Access & Management API Adapter.
 * Encapsulates all task-related HTTP calls and parameter mapping.
 */
export const tasksApi = {
  /**
   * Fetch paginated list of tasks matching the given filter criteria.
   */
  async getTasks(filters: TaskFilterParams = {}): Promise<PaginatedResponse<Task>> {
    const params: Record<string, string | number> = {
      page: filters.page || 1,
      per_page: filters.per_page || 10,
    }

    if (filters.status) {
      params.status = filters.status
    }
    if (filters.assigned_to) {
      params.assigned_to = filters.assigned_to
    }
    if (filters.search?.trim()) {
      params.search = filters.search.trim()
    }
    if (filters.sort_by) {
      params.sort_by = filters.sort_by
      params.sort_order = filters.sort_order || 'desc'
    }

    const response = await apiClient.get<PaginatedResponse<Task>>('/tasks', { params })
    return response.data
  },

  /**
   * Fetch a single task by ID.
   */
  async getTask(id: number): Promise<Task> {
    const response = await apiClient.get<{ data: Task }>(`/tasks/${id}`)
    return response.data.data
  },

  /**
   * Create a new task.
   */
  async createTask(payload: TaskFormPayload): Promise<Task> {
    const response = await apiClient.post<{ data: Task }>('/tasks', payload)
    return response.data.data
  },

  /**
   * Update an existing task.
   */
  async updateTask(id: number, payload: Partial<TaskFormPayload>): Promise<Task> {
    const response = await apiClient.put<{ data: Task }>(`/tasks/${id}`, payload)
    return response.data.data
  },

  /**
   * Soft-delete a task.
   */
  async deleteTask(id: number): Promise<void> {
    await apiClient.delete(`/tasks/${id}`)
  },
}
