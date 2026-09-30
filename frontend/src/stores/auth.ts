import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth.api'
import { extractApiErrorMessage } from '@/utils/errors'
import type { User, LoginCredentials } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const user = ref<User | null>(
    localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user') as string) : null
  )
  const loading = ref<boolean>(false)
  const errorMessage = ref<string | null>(null)

  const isAuthenticated = computed<boolean>(() => !!token.value && !!user.value)
  const isAdmin = computed<boolean>(() => user.value?.role === 'admin')

  async function login(credentials: LoginCredentials): Promise<boolean> {
    loading.value = true
    errorMessage.value = null
    try {
      const data = await authApi.login(credentials)

      token.value = data.token
      user.value = data.user

      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))

      return true
    } catch (err: unknown) {
      errorMessage.value = extractApiErrorMessage(err, 'Invalid credentials or server unavailable.')
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      if (token.value) {
        await authApi.logout()
      }
    } catch {
      // Gracefully clear local session even if network logout fails
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  async function fetchCurrentUser(): Promise<void> {
    if (!token.value) return
    try {
      const currentUser = await authApi.getCurrentUser()
      user.value = currentUser
      localStorage.setItem('user', JSON.stringify(currentUser))
    } catch {
      await logout()
    }
  }

  return {
    token,
    user,
    loading,
    errorMessage,
    isAuthenticated,
    isAdmin,
    login,
    logout,
    fetchCurrentUser,
  }
})
