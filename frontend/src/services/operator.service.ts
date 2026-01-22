import api from './api'
import type { Operator, Skill, Availability, Shift } from '@/types'

export const operatorService = {
  async getProfile(): Promise<Operator> {
    const response = await api.get('/operators/profile')
    return response.data
  },

  async updateProfile(data: Partial<Operator>): Promise<{ message: string; operator: Operator }> {
    const response = await api.put('/operators/profile', data)
    return response.data
  },

  async getSkills(): Promise<{ skills: Skill[] }> {
    const response = await api.get('/operators/skills')
    return response.data
  },

  async addSkill(skillId: number, proficiencyLevel: number): Promise<{ skill: Skill; message: string }> {
    const response = await api.post('/operators/skills', { skillId, proficiencyLevel })
    return response.data
  },

  async removeSkill(id: number): Promise<{ message: string }> {
    const response = await api.delete(`/operators/skills/${id}`)
    return response.data
  },

  async getAvailability(): Promise<{ availability: Availability[] }> {
    const response = await api.get('/operators/availability')
    return response.data
  },

  async setAvailability(slots: Availability[]): Promise<{ message: string }> {
    const response = await api.post('/operators/availability', { slots })
    return response.data
  },

  async getShifts(status?: string): Promise<{ shifts: Shift[] }> {
    const params = status ? { status } : {}
    const response = await api.get('/operators/shifts', { params })
    return response.data
  },
}
