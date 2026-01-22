import api from './api'
import type { Operator, Skill, Availability, Shift } from '@/types'

export const operatorService = {
  async getProfile(): Promise<Operator> {
    const response = await api.get('/me')
    return response.data
  },

  async updateProfile(data: Partial<Operator>): Promise<{ message: string; operator: Operator }> {
    const response = await api.put('/operator/profile', data)
    return response.data
  },

  // Get available skill definitions (for dropdown)
  async getAvailableSkills(): Promise<{ skills: any[]; categories: string[] }> {
    const response = await api.get('/operator/skills/available')
    return response.data
  },

  // Get operator's own skills
  async getSkills(): Promise<{ skills: Skill[] }> {
    const response = await api.get('/operator/skills')
    return response.data
  },

  async addSkill(skillDefinitionId: number, proficiencyLevel: string, yearsOfExperience?: number): Promise<{ skill: Skill; message: string }> {
    const response = await api.post('/operator/skills', { skillDefinitionId, proficiencyLevel, yearsOfExperience })
    return response.data
  },

  async removeSkill(id: number): Promise<{ message: string }> {
    const response = await api.delete(`/operator/skills/${id}`)
    return response.data
  },

  async getAvailability(): Promise<{ weeklyAvailability: any[] }> {
    const response = await api.get('/operator/availability/weekly')
    return response.data
  },

  async setWeeklyAvailability(dayOfWeek: number, startTime: string, endTime: string): Promise<{ message: string }> {
    const response = await api.post('/operator/availability/weekly', { dayOfWeek, startTime, endTime })
    return response.data
  },

  async deleteWeeklyAvailability(dayOfWeek: number): Promise<{ message: string }> {
    const response = await api.delete(`/operator/availability/weekly/${dayOfWeek}`)
    return response.data
  },

  // Batch update: clears all and sets new slots
  async setAvailability(slots: Availability[]): Promise<void> {
    // First, clear existing by deleting each day, then add new slots
    const existingResponse = await this.getAvailability()
    const existingDays = new Set((existingResponse.weeklyAvailability || []).map((s: any) => s.dayOfWeek))

    // Delete existing slots for days that have them
    for (const day of existingDays) {
      await this.deleteWeeklyAvailability(day)
    }

    // Add new slots
    for (const slot of slots) {
      await this.setWeeklyAvailability(slot.dayOfWeek, slot.startTime, slot.endTime)
    }
  },

  async getShifts(status?: string): Promise<{ shifts: Shift[] }> {
    const params = status ? { status } : {}
    const response = await api.get('/operator/shifts', { params })
    return response.data
  },
}
