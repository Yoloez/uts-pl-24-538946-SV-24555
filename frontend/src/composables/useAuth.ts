import { ref, computed } from 'vue'
import apiClient from '@/services/api'
import type { User, AuthResponse } from '@/types'

const token = ref<string | null>(localStorage.getItem('token'))
const user = ref<User | null>(
  localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user') as string) : null
)

export function useAuth() {
  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isPenghuni = computed(() => user.value?.role === 'penghuni')

  const setAuth = (newToken: string, newUser: User) => {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('token', newToken)
    localStorage.setItem('user', JSON.stringify(newUser))
  }

  const clearAuth = () => {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  const login = async (email: string, password: string): Promise<AuthResponse> => {
    const response = await apiClient.post<AuthResponse>('/login', { email, password })
    setAuth(response.data.access_token, response.data.user)
    return response.data
  }

  const register = async (name: string, email: string, password: string, role = 'penghuni'): Promise<AuthResponse> => {
    const response = await apiClient.post<AuthResponse>('/register', {
      name,
      email,
      password,
      role,
    })
    setAuth(response.data.access_token, response.data.user)
    return response.data
  }

  const logout = async () => {
    try {
      if (token.value) {
        await apiClient.post('/logout')
      }
    } catch {
      // Abaikan jika error network/expired
    } finally {
      clearAuth()
    }
  }

  const fetchMe = async (): Promise<User | null> => {
    if (!token.value) return null
    try {
      const response = await apiClient.get<{ user: User }>('/me')
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(response.data.user))
      return response.data.user
    } catch {
      return null
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    isAdmin,
    isPenghuni,
    login,
    register,
    logout,
    fetchMe,
    clearAuth,
    setAuth,
  }
}
