import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useOperatorStore } from '@/stores/operator'
import { operatorService } from '@/services/operator.service'

vi.mock('@/services/operator.service', () => ({
  operatorService: {
    getProfile: vi.fn(),
    updateProfile: vi.fn(),
    getSkills: vi.fn(),
    addSkill: vi.fn(),
    removeSkill: vi.fn(),
    getAvailability: vi.fn(),
    setAvailability: vi.fn(),
    getShifts: vi.fn(),
  },
}))

describe('Operator Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchProfile', () => {
    it('fetches and sets operator profile', async () => {
      const mockProfile = {
        id: 1,
        email: 'operator@test.com',
        firstName: 'John',
        lastName: 'Doe',
        status: 'active',
        regionCode: 'US-CA',
        timezoneId: 'America/Los_Angeles',
      }
      vi.mocked(operatorService.getProfile).mockResolvedValue({ profile: mockProfile })

      const store = useOperatorStore()
      await store.fetchProfile()

      expect(store.profile).toEqual(mockProfile)
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('handles fetch error', async () => {
      vi.mocked(operatorService.getProfile).mockRejectedValue(new Error('Network error'))

      const store = useOperatorStore()
      await store.fetchProfile()

      expect(store.profile).toBeNull()
      expect(store.error).toBe('Failed to fetch profile')
    })
  })

  describe('updateProfile', () => {
    it('updates operator profile', async () => {
      vi.mocked(operatorService.updateProfile).mockResolvedValue({ message: 'Updated' })

      const store = useOperatorStore()
      store.profile = {
        id: 1,
        email: 'op@test.com',
        firstName: 'Old',
        lastName: 'Name',
        status: 'active',
      }

      await store.updateProfile({ firstName: 'New', lastName: 'Name' })

      expect(operatorService.updateProfile).toHaveBeenCalledWith({ firstName: 'New', lastName: 'Name' })
    })
  })

  describe('skills management', () => {
    it('fetches skills list', async () => {
      const mockSkills = [
        { id: 1, skillId: 1, skillName: 'Robot Operation', category: 'Operations', proficiencyLevel: 4 },
        { id: 2, skillId: 2, skillName: 'Safety Training', category: 'Safety', proficiencyLevel: 5 },
      ]
      vi.mocked(operatorService.getSkills).mockResolvedValue({ skills: mockSkills })

      const store = useOperatorStore()
      await store.fetchSkills()

      expect(store.skills).toEqual(mockSkills)
      expect(store.skills.length).toBe(2)
    })

    it('adds a new skill', async () => {
      const newSkill = { id: 3, skillId: 3, skillName: 'Forklift', category: 'Equipment', proficiencyLevel: 3 }
      vi.mocked(operatorService.addSkill).mockResolvedValue({ skill: newSkill })
      vi.mocked(operatorService.getSkills).mockResolvedValue({ skills: [newSkill] })

      const store = useOperatorStore()
      await store.addSkill(3, 3)

      expect(operatorService.addSkill).toHaveBeenCalledWith(3, 3)
    })

    it('removes a skill', async () => {
      vi.mocked(operatorService.removeSkill).mockResolvedValue({ message: 'Removed' })
      vi.mocked(operatorService.getSkills).mockResolvedValue({ skills: [] })

      const store = useOperatorStore()
      store.skills = [{ id: 1, skillId: 1, skillName: 'Test', category: 'Test', proficiencyLevel: 1 }]

      await store.removeSkill(1)

      expect(operatorService.removeSkill).toHaveBeenCalledWith(1)
    })
  })

  describe('availability management', () => {
    it('fetches weekly availability', async () => {
      const mockAvailability = [
        { id: 1, dayOfWeek: 1, startTime: '09:00', endTime: '17:00' },
        { id: 2, dayOfWeek: 2, startTime: '10:00', endTime: '18:00' },
      ]
      vi.mocked(operatorService.getAvailability).mockResolvedValue({ availability: mockAvailability })

      const store = useOperatorStore()
      await store.fetchAvailability()

      expect(store.availability).toEqual(mockAvailability)
      expect(store.availability.length).toBe(2)
    })

    it('sets new availability slots', async () => {
      const newSlots = [
        { dayOfWeek: 1, startTime: '08:00', endTime: '16:00' },
        { dayOfWeek: 3, startTime: '09:00', endTime: '17:00' },
      ]
      vi.mocked(operatorService.setAvailability).mockResolvedValue({ message: 'Updated' })
      vi.mocked(operatorService.getAvailability).mockResolvedValue({ availability: newSlots })

      const store = useOperatorStore()
      await store.setAvailability(newSlots)

      expect(operatorService.setAvailability).toHaveBeenCalledWith(newSlots)
    })

    it('handles invalid availability (end before start)', async () => {
      vi.mocked(operatorService.setAvailability).mockRejectedValue(new Error('Invalid time range'))

      const store = useOperatorStore()
      await expect(store.setAvailability([
        { dayOfWeek: 1, startTime: '17:00', endTime: '09:00' },
      ])).rejects.toThrow()
    })
  })

  describe('shifts management', () => {
    it('fetches assigned shifts', async () => {
      const mockShifts = [
        {
          id: 1,
          job: { id: 1, title: 'Test Job', hourlyRate: 25, currency: 'USD' },
          site: { id: 1, name: 'Test Site' },
          startTimeUtc: '2024-01-15T09:00:00Z',
          endTimeUtc: '2024-01-15T17:00:00Z',
          status: 'assigned',
          durationMinutes: 480,
        },
      ]
      vi.mocked(operatorService.getShifts).mockResolvedValue({ shifts: mockShifts })

      const store = useOperatorStore()
      await store.fetchShifts()

      expect(store.shifts).toEqual(mockShifts)
      expect(store.shifts[0].status).toBe('assigned')
    })

    it('returns empty array when no shifts', async () => {
      vi.mocked(operatorService.getShifts).mockResolvedValue({ shifts: [] })

      const store = useOperatorStore()
      await store.fetchShifts()

      expect(store.shifts).toEqual([])
    })
  })

  describe('loading state', () => {
    it('sets loading during async operations', async () => {
      vi.mocked(operatorService.getProfile).mockImplementation(async () => {
        return new Promise((resolve) => setTimeout(() => resolve({ profile: {} }), 100))
      })

      const store = useOperatorStore()
      const promise = store.fetchProfile()

      expect(store.loading).toBe(true)

      await promise

      expect(store.loading).toBe(false)
    })
  })
})
