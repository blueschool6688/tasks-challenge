import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios'

interface CustomRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean
}

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

apiClient.interceptors.response.use(
  (response) => response,
  async (error: unknown) => {
    if (axios.isAxiosError(error) && error.config) {
      const config = error.config as CustomRequestConfig
      const status = error.response?.status
      if (status === 419 && !config._retry) {
        config._retry = true
        try {
          await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
          return apiClient(config)
        } catch (csrfError: unknown) {
          return Promise.reject(csrfError)
        }
      }
      if (status === 401 && !config.url?.includes('/login')) {
        window.dispatchEvent(new CustomEvent('app:unauthorized'))
      }
    }

    return Promise.reject(error)
  }
)

export default apiClient
