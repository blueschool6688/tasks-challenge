import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/axios'
import type { User, LoginCredentials, AuthResponse } from '@/types'

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
      const response = await apiClient.post<AuthResponse>('/login', credentials)
      const data = response.data

      token.value = data.token
      user.value = data.user

      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))

      return true
    } catch (err: unknown) {
      if (
        err &&
        typeof err === 'object' &&
        'response' in err &&
        err.response &&
        typeof err.response === 'object' &&
        'data' in err.response &&
        err.response.data &&
        typeof err.response.data === 'object' &&
        'message' in err.response.data
      ) {
        errorMessage.value = String((err.response.data as { message: string }).message)
      } else {
        errorMessage.value = 'Failed to connect to authentication server.'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      if (token.value) {
        await apiClient.post('/logout')
      }
    } catch {
      // Ignore network errors during logout
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
      const response = await apiClient.get<User>('/me')
      user.value = response.data
      localStorage.setItem('user', JSON.stringify(response.data))
    } catch {
      logout()
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
