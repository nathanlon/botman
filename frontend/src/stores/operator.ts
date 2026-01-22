import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Skill, Availability, Shift } from '@/types'
import { operatorService } from '@/services/operator.service'

export const useOperatorStore = defineStore('operator', () => {
  const skills = ref<Skill[]>([])
  const availability = ref<Availability[]>([])
  const shifts = ref<Shift[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchSkills(): Promise<void> {
    loading.value = true
    try {
      const response = await operatorService.getSkills()
      skills.value = response.skills
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to fetch skills'
    } finally {
      loading.value = false
    }
  }

  async function addSkill(skillId: number, proficiencyLevel: number): Promise<void> {
    loading.value = true
    try {
      const response = await operatorService.addSkill(skillId, proficiencyLevel)
      skills.value.push(response.skill)
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to add skill'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function removeSkill(id: number): Promise<void> {
    loading.value = true
    try {
      await operatorService.removeSkill(id)
      skills.value = skills.value.filter(s => s.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to remove skill'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchAvailability(): Promise<void> {
    loading.value = true
    try {
      const response = await operatorService.getAvailability()
      availability.value = response.availability
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to fetch availability'
    } finally {
      loading.value = false
    }
  }

  async function setAvailability(slots: Availability[]): Promise<void> {
    loading.value = true
    try {
      await operatorService.setAvailability(slots)
      availability.value = slots
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to set availability'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchShifts(status?: string): Promise<void> {
    loading.value = true
    try {
      const response = await operatorService.getShifts(status)
      shifts.value = response.shifts
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to fetch shifts'
    } finally {
      loading.value = false
    }
  }

  return {
    skills,
    availability,
    shifts,
    loading,
    error,
    fetchSkills,
    addSkill,
    removeSkill,
    fetchAvailability,
    setAvailability,
    fetchShifts,
  }
})
