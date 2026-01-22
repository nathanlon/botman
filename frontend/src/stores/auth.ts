import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Operator, ClientUser, LoginCredentials, OperatorRegistration, ClientRegistration } from '@/types'
import { authService } from '@/services/auth.service'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<Operator | ClientUser | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isOperator = computed(() => user.value?.type === 'operator')
  const isClient = computed(() => user.value?.type === 'client')
  const isClientAdmin = computed(() =>
    user.value?.type === 'client' && (user.value as ClientUser).role === 'admin'
  )

  async function login(credentials: LoginCredentials): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const response = await authService.login(credentials)
      token.value = response.token
      localStorage.setItem('token', response.token)
      await fetchUser()
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function registerOperator(data: OperatorRegistration): Promise<void> {
    loading.value = true
    error.value = null
    try {
      await authService.registerOperator(data)
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function registerClient(data: ClientRegistration): Promise<void> {
    loading.value = true
    error.value = null
    try {
      await authService.registerClient(data)
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) return
    try {
      user.value = await authService.getCurrentUser()
    } catch (err) {
      logout()
    }
  }

  function logout(): void {
    authService.logout()
    token.value = null
    user.value = null
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isOperator,
    isClient,
    isClientAdmin,
    login,
    registerOperator,
    registerClient,
    fetchUser,
    logout,
  }
})
