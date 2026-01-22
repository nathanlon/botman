import api from './api'
import type {
  LoginCredentials,
  OperatorRegistration,
  ClientRegistration,
  Operator,
  ClientUser
} from '@/types'

export const authService = {
  async login(credentials: LoginCredentials): Promise<{ token: string }> {
    const response = await api.post('/login_check', credentials)
    return response.data
  },

  async registerOperator(data: OperatorRegistration): Promise<{ operator: Operator; message: string }> {
    const response = await api.post('/auth/register/operator', data)
    return response.data
  },

  async registerClient(data: ClientRegistration): Promise<{ user: ClientUser; organization: any; message: string }> {
    const response = await api.post('/auth/register/client', data)
    return response.data
  },

  async getCurrentUser(): Promise<Operator | ClientUser> {
    const response = await api.get('/auth/me')
    return response.data
  },

  logout(): void {
    localStorage.removeItem('token')
  },
}
