import api from './api'
import type { Shift, Job, Session } from '@/types'

export const shiftService = {
  async getAvailableShifts(from?: string, to?: string): Promise<{ shifts: Shift[] }> {
    const params: Record<string, string> = {}
    if (from) params.from = from
    if (to) params.to = to
    const response = await api.get('/shifts/available', { params })
    return response.data
  },

  async getShift(id: number): Promise<Shift> {
    const response = await api.get(`/shifts/${id}`)
    return response.data
  },

  async acceptShift(id: number): Promise<{ shift: Shift; message: string }> {
    const response = await api.post(`/shifts/${id}/accept`)
    return response.data
  },

  async cancelShift(id: number, reason?: string): Promise<{ shift: Shift; message: string }> {
    const response = await api.post(`/shifts/${id}/cancel`, { reason })
    return response.data
  },

  async startSession(shiftId: number): Promise<{ session: Session; shift: Shift; message: string }> {
    const response = await api.post(`/shifts/${shiftId}/start-session`)
    return response.data
  },

  async endSession(shiftId: number, sessionId: number, reason?: string): Promise<{ session: Session; message: string }> {
    const response = await api.post(`/shifts/${shiftId}/session/${sessionId}/end`, { reason })
    return response.data
  },

  async getMarketplace(): Promise<{ jobs: Job[] }> {
    const response = await api.get('/jobs/marketplace')
    return response.data
  },

  async createJob(data: Partial<Job> & { siteId: number; robotIds?: number[] }): Promise<{ job: Job; message: string }> {
    const response = await api.post('/jobs', data)
    return response.data
  },

  async getJob(id: number): Promise<Job> {
    const response = await api.get(`/jobs/${id}`)
    return response.data
  },

  async activateJob(id: number): Promise<{ shiftsGenerated: number; message: string }> {
    const response = await api.post(`/jobs/${id}/activate`)
    return response.data
  },
}
