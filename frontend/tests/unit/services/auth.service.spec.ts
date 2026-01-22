import { describe, it, expect, beforeEach, vi } from 'vitest'
import { authService } from '@/services/auth.service'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

describe('Auth Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('login', () => {
    it('sends login request with correct credentials', async () => {
      const mockResponse = {
        data: {
          token: 'jwt-token',
          user: { id: 1, email: 'test@test.com', role: 'operator' },
        },
      }
      vi.mocked(api.post).mockResolvedValue(mockResponse)

      const result = await authService.login('test@test.com', 'password123')

      expect(api.post).toHaveBeenCalledWith('/auth/login', {
        email: 'test@test.com',
        password: 'password123',
      })
      expect(result).toEqual(mockResponse.data)
    })

    it('propagates login error', async () => {
      vi.mocked(api.post).mockRejectedValue(new Error('Invalid credentials'))

      await expect(authService.login('bad@test.com', 'wrong')).rejects.toThrow('Invalid credentials')
    })
  })

  describe('register', () => {
    it('sends operator registration request', async () => {
      const mockResponse = {
        data: { message: 'Success', userId: 1 },
      }
      vi.mocked(api.post).mockResolvedValue(mockResponse)

      const result = await authService.register({
        email: 'new@test.com',
        password: 'SecurePass123!',
        firstName: 'John',
        lastName: 'Doe',
        userType: 'operator',
      })

      expect(api.post).toHaveBeenCalledWith('/auth/register', expect.objectContaining({
        email: 'new@test.com',
        userType: 'operator',
      }))
      expect(result).toEqual(mockResponse.data)
    })

    it('sends client registration with company name', async () => {
      const mockResponse = {
        data: { message: 'Success', userId: 1, organizationId: 1 },
      }
      vi.mocked(api.post).mockResolvedValue(mockResponse)

      const result = await authService.register({
        email: 'client@company.com',
        password: 'SecurePass123!',
        firstName: 'Jane',
        lastName: 'Smith',
        userType: 'client',
        companyName: 'Acme Corp',
      })

      expect(api.post).toHaveBeenCalledWith('/auth/register', expect.objectContaining({
        companyName: 'Acme Corp',
        userType: 'client',
      }))
      expect(result.organizationId).toBe(1)
    })

    it('propagates registration error', async () => {
      vi.mocked(api.post).mockRejectedValue(new Error('Email already exists'))

      await expect(authService.register({
        email: 'existing@test.com',
        password: 'Pass123!',
        firstName: 'Test',
        lastName: 'User',
        userType: 'operator',
      })).rejects.toThrow('Email already exists')
    })
  })

  describe('getCurrentUser', () => {
    it('fetches current user profile', async () => {
      const mockUser = {
        id: 1,
        email: 'current@test.com',
        firstName: 'Current',
        lastName: 'User',
        role: 'operator',
      }
      vi.mocked(api.get).mockResolvedValue({ data: mockUser })

      const result = await authService.getCurrentUser()

      expect(api.get).toHaveBeenCalledWith('/auth/me')
      expect(result).toEqual(mockUser)
    })

    it('propagates authentication error', async () => {
      vi.mocked(api.get).mockRejectedValue(new Error('Unauthorized'))

      await expect(authService.getCurrentUser()).rejects.toThrow('Unauthorized')
    })
  })
})
