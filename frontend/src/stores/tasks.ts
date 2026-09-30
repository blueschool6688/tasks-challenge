import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tasksApi } from '@/api/tasks.api'
import { authApi } from '@/api/auth.api'
import { extractApiErrorMessage } from '@/utils/errors'
import type {
  Task,
  User,
  TaskFilterParams,
  TaskFormPayload,
  PaginationMeta,
} from '@/types'

export interface NotificationState {
  show: boolean
  text: string
  color: 'success' | 'error' | 'info' | 'warning'
}

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

  // Filter state
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
  const snackbar = ref<NotificationState>({
    show: false,
    text: '',
    color: 'success',
  })

  function notify(text: string, color: NotificationState['color'] = 'success'): void {
    snackbar.value = {
      show: true,
      text,
      color,
    }
  }

  async function fetchTasks(): Promise<void> {
    loading.value = true
    try {
      const response = await tasksApi.getTasks(filters.value)
      tasks.value = response.data
      pagination.value = response.meta
    } catch (err: unknown) {
      notify(extractApiErrorMessage(err, 'Failed to load tasks. Please try again.'), 'error')
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers(): Promise<void> {
    try {
      users.value = await authApi.getUsers()
    } catch (err: unknown) {
      notify(extractApiErrorMessage(err, 'Failed to load users list.'), 'error')
    }
  }

  async function createTask(payload: TaskFormPayload): Promise<boolean> {
    loading.value = true
    try {
      await tasksApi.createTask(payload)
      notify('Task created successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      notify(extractApiErrorMessage(err, 'Failed to create task.'), 'error')
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateTask(id: number, payload: Partial<TaskFormPayload>): Promise<boolean> {
    loading.value = true
    try {
      await tasksApi.updateTask(id, payload)
      notify('Task updated successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      notify(extractApiErrorMessage(err, 'Failed to update task.'), 'error')
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteTask(id: number): Promise<boolean> {
    loading.value = true
    try {
      await tasksApi.deleteTask(id)
      notify('Task deleted successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      notify(extractApiErrorMessage(err, 'Failed to delete task.'), 'error')
      return false
    } finally {
      loading.value = false
    }
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
