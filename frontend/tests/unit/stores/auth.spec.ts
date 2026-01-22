import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/services/auth.service'

vi.mock('@/services/auth.service', () => ({
  authService: {
    login: vi.fn(),
    register: vi.fn(),
    getCurrentUser: vi.fn(),
  },
}))

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.getItem = vi.fn()
    localStorage.setItem = vi.fn()
    localStorage.removeItem = vi.fn()
  })

  it('initializes with null user when no token', () => {
    localStorage.getItem = vi.fn().mockReturnValue(null)
    const store = useAuthStore()
    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('initializes with token from localStorage', () => {
    localStorage.getItem = vi.fn().mockReturnValue('test-token')
    const store = useAuthStore()
    expect(store.token).toBe('test-token')
  })

  describe('login', () => {
    it('successfully logs in an operator', async () => {
      const mockResponse = {
        token: 'jwt-token',
        user: {
          id: 1,
          email: 'operator@test.com',
          firstName: 'John',
          lastName: 'Doe',
          role: 'operator',
        },
      }
      vi.mocked(authService.login).mockResolvedValue(mockResponse)

      const store = useAuthStore()
      await store.login('operator@test.com', 'password123')

      expect(authService.login).toHaveBeenCalledWith('operator@test.com', 'password123')
      expect(store.token).toBe('jwt-token')
      expect(store.user).toEqual(mockResponse.user)
      expect(store.isAuthenticated).toBe(true)
      expect(localStorage.setItem).toHaveBeenCalledWith('token', 'jwt-token')
    })

    it('handles login failure', async () => {
      vi.mocked(authService.login).mockRejectedValue(new Error('Invalid credentials'))

      const store = useAuthStore()
      await expect(store.login('bad@test.com', 'wrong')).rejects.toThrow('Invalid credentials')

      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('register', () => {
    it('successfully registers an operator', async () => {
      const mockResponse = {
        message: 'Registration successful',
        userId: 1,
      }
      vi.mocked(authService.register).mockResolvedValue(mockResponse)

      const store = useAuthStore()
      const result = await store.register({
        email: 'new@test.com',
        password: 'SecurePass123!',
        firstName: 'Jane',
        lastName: 'Doe',
        userType: 'operator',
      })

      expect(result).toEqual(mockResponse)
      expect(authService.register).toHaveBeenCalled()
    })

    it('successfully registers a client with company', async () => {
      const mockResponse = {
        message: 'Registration successful',
        userId: 1,
        organizationId: 1,
      }
      vi.mocked(authService.register).mockResolvedValue(mockResponse)

      const store = useAuthStore()
      const result = await store.register({
        email: 'client@company.com',
        password: 'SecurePass123!',
        firstName: 'Bob',
        lastName: 'Smith',
        userType: 'client',
        companyName: 'Acme Corp',
      })

      expect(result.organizationId).toBe(1)
    })
  })

  describe('logout', () => {
    it('clears user state and token', async () => {
      localStorage.getItem = vi.fn().mockReturnValue('test-token')
      const store = useAuthStore()
      store.user = { id: 1, email: 'test@test.com', firstName: 'Test', lastName: 'User', role: 'operator' }

      await store.logout()

      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.removeItem).toHaveBeenCalledWith('token')
    })
  })

  describe('fetchCurrentUser', () => {
    it('fetches and sets current user', async () => {
      const mockUser = {
        id: 1,
        email: 'current@test.com',
        firstName: 'Current',
        lastName: 'User',
        role: 'operator',
      }
      vi.mocked(authService.getCurrentUser).mockResolvedValue(mockUser)

      const store = useAuthStore()
      await store.fetchCurrentUser()

      expect(store.user).toEqual(mockUser)
    })

    it('handles fetch failure gracefully', async () => {
      vi.mocked(authService.getCurrentUser).mockRejectedValue(new Error('Unauthorized'))

      const store = useAuthStore()
      await store.fetchCurrentUser()

      expect(store.user).toBeNull()
    })
  })

  describe('computed properties', () => {
    it('isOperator returns true for operators', () => {
      const store = useAuthStore()
      store.user = { id: 1, email: 'op@test.com', firstName: 'Op', lastName: 'User', role: 'operator' }
      expect(store.isOperator).toBe(true)
      expect(store.isClient).toBe(false)
    })

    it('isClient returns true for clients', () => {
      const store = useAuthStore()
      store.user = { id: 1, email: 'client@test.com', firstName: 'Client', lastName: 'User', role: 'client' }
      expect(store.isClient).toBe(true)
      expect(store.isOperator).toBe(false)
    })
  })
})
