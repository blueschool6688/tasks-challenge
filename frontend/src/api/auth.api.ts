import apiClient from './axios'
import type { User, LoginCredentials, AuthResponse } from '@/types'

/**
 * Authentication and User Identity API Adapter.
 * Encapsulates all auth-related HTTP network calls behind typed methods.
 */
export const authApi = {
  /**
   * Authenticate with email and password.
   * Sanctum personal access token is stored automatically in an HttpOnly cookie by backend.
   */
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await apiClient.post<AuthResponse>('/login', credentials)
    return response.data
  },

  /**
   * Invalidate current Sanctum bearer token.
   */
  async logout(): Promise<void> {
    await apiClient.post('/logout')
  },

  /**
   * Fetch authenticated user details.
   */
  async getCurrentUser(): Promise<User> {
    const response = await apiClient.get<User>('/me')
    return response.data
  },

  /**
   * Fetch available team members list for task assignments.
   */
  async getUsers(): Promise<User[]> {
    const response = await apiClient.get<User[]>('/users')
    return response.data
  },
}
