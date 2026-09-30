import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/axios'
import type {
  Task,
  User,
  TaskFilterParams,
  TaskFormPayload,
  PaginationMeta,
  PaginatedResponse,
} from '@/types'

export const useTaskStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const users = ref<User[]>([])
  const loading = ref<boolean>(false)
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    from: 0,
    last_page: 1,
    per_page: 10,
    to: 0,
    total: 0,
  })

  // Filters
  const filters = ref<TaskFilterParams>({
    status: '',
    assigned_to: '',
    search: '',
    page: 1,
    per_page: 10,
    sort_by: 'created_at',
    sort_order: 'desc',
  })

  // Feedback notification
  const snackbar = ref<{
    show: boolean
    text: string
    color: string
  }>({
    show: false,
    text: '',
    color: 'success',
  })

  function notify(text: string, color: 'success' | 'error' | 'info' = 'success'): void {
    snackbar.value = {
      show: true,
      text,
      color,
    }
  }

  async function fetchTasks(): Promise<void> {
    loading.value = true
    try {
      const params: Record<string, string | number> = {
        page: filters.value.page || 1,
        per_page: filters.value.per_page || 10,
      }

      if (filters.value.status) {
        params.status = filters.value.status
      }
      if (filters.value.assigned_to) {
        params.assigned_to = filters.value.assigned_to
      }
      if (filters.value.search?.trim()) {
        params.search = filters.value.search.trim()
      }
      if (filters.value.sort_by) {
        params.sort_by = filters.value.sort_by
        params.sort_order = filters.value.sort_order || 'desc'
      }

      const response = await apiClient.get<PaginatedResponse<Task>>('/tasks', { params })
      tasks.value = response.data.data
      pagination.value = response.data.meta
    } catch {
      notify('Failed to load tasks. Please try again.', 'error')
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers(): Promise<void> {
    try {
      const response = await apiClient.get<User[]>('/users')
      users.value = response.data
    } catch {
      notify('Failed to load users list.', 'error')
    }
  }

  async function createTask(payload: TaskFormPayload): Promise<boolean> {
    loading.value = true
    try {
      await apiClient.post('/tasks', payload)
      notify('Task created successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      handleApiError(err, 'Failed to create task.')
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateTask(id: number, payload: Partial<TaskFormPayload>): Promise<boolean> {
    loading.value = true
    try {
      await apiClient.put(`/tasks/${id}`, payload)
      notify('Task updated successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      handleApiError(err, 'Failed to update task.')
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteTask(id: number): Promise<boolean> {
    loading.value = true
    try {
      await apiClient.delete(`/tasks/${id}`)
      notify('Task deleted successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      handleApiError(err, 'Failed to delete task.')
      return false
    } finally {
      loading.value = false
    }
  }

  function handleApiError(err: unknown, defaultMsg: string): void {
    if (
      err &&
      typeof err === 'object' &&
      'response' in err &&
      err.response &&
      typeof err.response === 'object' &&
      'data' in err.response &&
      err.response.data &&
      typeof err.response.data === 'object'
    ) {
      const data = err.response.data as { message?: string; errors?: Record<string, string[]> }
      if (data.errors) {
        const firstField = Object.keys(data.errors)[0]
        const firstError = data.errors[firstField]?.[0]
        notify(firstError || defaultMsg, 'error')
        return
      }
      if (data.message) {
        notify(data.message, 'error')
        return
      }
    }
    notify(defaultMsg, 'error')
  }

  return {
    tasks,
    users,
    loading,
    pagination,
    filters,
    snackbar,
    notify,
    fetchTasks,
    fetchUsers,
    createTask,
    updateTask,
    deleteTask,
  }
})
