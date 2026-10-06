import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tasksApi } from '@/api/tasks.api'
import { authApi } from '@/api/auth.api'
import { useNotificationStore } from '@/stores/notification'
import { extractApiErrorMessage } from '@/utils/errors'
import type {
  Task,
  User,
  TaskFilterParams,
  TaskFormPayload,
  PaginationMeta,
} from '@/types'

export const useTaskStore = defineStore('tasks', () => {
  const notificationStore = useNotificationStore()

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

  // Filter and sort state
  const filters = ref<TaskFilterParams>({
    status: '',
    assigned_to: '',
    search: '',
    page: 1,
    per_page: 10,
    sort_by: 'created_at',
    sort_order: 'desc',
  })

  async function fetchTasks(): Promise<void> {
    loading.value = true
    try {
      const response = await tasksApi.getTasks(filters.value)
      tasks.value = response.data
      pagination.value = response.meta
    } catch (err: unknown) {
      notificationStore.notify(
        extractApiErrorMessage(err, 'Failed to load tasks. Please try again.'),
        'error'
      )
    } finally {
      loading.value = false
    }
  }

  async function fetchUsers(): Promise<void> {
    try {
      users.value = await authApi.getUsers()
    } catch (err: unknown) {
      notificationStore.notify(
        extractApiErrorMessage(err, 'Failed to load users list.'),
        'error'
      )
    }
  }

  async function createTask(payload: TaskFormPayload): Promise<boolean> {
    loading.value = true
    try {
      await tasksApi.createTask(payload)
      notificationStore.notify('Task created successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      notificationStore.notify(extractApiErrorMessage(err, 'Failed to create task.'), 'error')
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateTask(id: number, payload: Partial<TaskFormPayload>): Promise<boolean> {
    loading.value = true
    try {
      await tasksApi.updateTask(id, payload)
      notificationStore.notify('Task updated successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      notificationStore.notify(extractApiErrorMessage(err, 'Failed to update task.'), 'error')
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteTask(id: number): Promise<boolean> {
    loading.value = true
    try {
      await tasksApi.deleteTask(id)
      notificationStore.notify('Task deleted successfully.', 'success')
      await fetchTasks()
      return true
    } catch (err: unknown) {
      notificationStore.notify(extractApiErrorMessage(err, 'Failed to delete task.'), 'error')
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
    fetchTasks,
    fetchUsers,
    createTask,
    updateTask,
    deleteTask,
  }
})
