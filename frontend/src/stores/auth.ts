import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth.api'
import type { User, LoginCredentials } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  // Purge any legacy stateless tokens left in the browser's localStorage
  if (typeof window !== 'undefined' && window.localStorage) {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  const user = ref<User | null>(null)
  const isInitialized = ref<boolean>(false)
  const loading = ref<boolean>(false)

  const isAuthenticated = computed<boolean>(() => !!user.value)
  const isAdmin = computed<boolean>(() => user.value?.role === 'admin')

  /**
   * Hydrate auth state from the backend session cookie on app initialization.
   */
  async function init(): Promise<void> {
    if (isInitialized.value) return

    try {
      const currentUser = await authApi.getCurrentUser()
      user.value = currentUser
    } catch {
      user.value = null
    } finally {
      isInitialized.value = true
    }
  }

  /**
   * Log in via stateful cookie session.
   * Throws on error so the caller component can display local error feedback.
   */
  async function login(credentials: LoginCredentials): Promise<void> {
    loading.value = true
    try {
      const data = await authApi.login(credentials)
      user.value = data.user
    } finally {
      loading.value = false
    }
  }

  /**
   * Log out and terminate the backend session.
   */
  async function logout(): Promise<void> {
    loading.value = true
    try {
      await authApi.logout()
    } catch {
      // Gracefully clear local memory even if network call fails
    } finally {
      user.value = null
      loading.value = false
    }
  }

  /**
   * Reset user state when 401 occurs.
   */
  function handleUnauthorized(): void {
    user.value = null
  }

  return {
    user,
    isInitialized,
    loading,
    isAuthenticated,
    isAdmin,
    init,
    login,
    logout,
    handleUnauthorized,
  }
})
