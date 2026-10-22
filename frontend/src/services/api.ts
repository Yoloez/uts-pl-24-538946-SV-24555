import axios, { type AxiosInstance, type InternalAxiosRequestConfig, type AxiosResponse } from 'axios'

/**
 * Alamat API diambil dari file konfigurasi environment (.env / VITE_API_BASE_URL).
 * Tidak ditulis secara hardcoded di dalam komponen.
 */
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'

export const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 10000,
})

// Request Interceptor: Otomatis lampirkan Bearer token dari localStorage pada setiap request berikutnya
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Tangani error global seperti token kadaluarsa / 401
apiClient.interceptors.response.use(
  (response: AxiosResponse) => {
    return response
  },
  (error) => {
    if (error.response && error.response.status === 401) {
      // Jika token tidak ada / sudah tidak valid, hapus session lokal
      const currentPath = window.location.pathname
      const isLoginRequest = error.config?.url?.includes('/login')

      if (!isLoginRequest) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.dispatchEvent(new CustomEvent('auth:unauthorized'))
      }
    }
    return Promise.reject(error)
  }
)

export default apiClient
