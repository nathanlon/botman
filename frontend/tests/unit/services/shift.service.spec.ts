import { describe, it, expect, beforeEach, vi } from 'vitest'
import { shiftService } from '@/services/shift.service'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('Shift Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getAvailableShifts', () => {
    it('fetches available shifts', async () => {
      const mockShifts = {
        shifts: [
          { id: 1, status: 'unassigned', startTimeUtc: '2024-01-15T09:00:00Z' },
          { id: 2, status: 'unassigned', startTimeUtc: '2024-01-16T09:00:00Z' },
        ],
      }
      vi.mocked(api.get).mockResolvedValue({ data: mockShifts })

      const result = await shiftService.getAvailableShifts()

      expect(api.get).toHaveBeenCalledWith('/shifts/available')
      expect(result).toEqual(mockShifts)
    })

    it('handles empty result', async () => {
      vi.mocked(api.get).mockResolvedValue({ data: { shifts: [] } })

      const result = await shiftService.getAvailableShifts()

      expect(result.shifts).toEqual([])
    })
  })

  describe('getMarketplace', () => {
    it('fetches marketplace jobs', async () => {
      const mockJobs = {
        jobs: [
          { id: 1, title: 'Robot Operator', hourlyRateAmount: 25 },
          { id: 2, title: 'Warehouse Tech', hourlyRateAmount: 30 },
        ],
      }
      vi.mocked(api.get).mockResolvedValue({ data: mockJobs })

      const result = await shiftService.getMarketplace()

      expect(api.get).toHaveBeenCalledWith('/jobs/marketplace')
      expect(result).toEqual(mockJobs)
    })
  })

  describe('acceptShift', () => {
    it('accepts a shift', async () => {
      const mockResponse = { message: 'Shift accepted', shift: { id: 1, status: 'assigned' } }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await shiftService.acceptShift(1)

      expect(api.post).toHaveBeenCalledWith('/shifts/1/accept')
      expect(result).toEqual(mockResponse)
    })

    it('handles conflict error', async () => {
      vi.mocked(api.post).mockRejectedValue({
        response: { status: 409, data: { error: 'Shift already assigned' } },
      })

      await expect(shiftService.acceptShift(1)).rejects.toMatchObject({
        response: { status: 409 },
      })
    })
  })

  describe('cancelShift', () => {
    it('cancels a shift with reason', async () => {
      const mockResponse = { message: 'Shift cancelled' }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await shiftService.cancelShift(1, 'Personal emergency')

      expect(api.post).toHaveBeenCalledWith('/shifts/1/cancel', { reason: 'Personal emergency' })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('startSession', () => {
    it('starts a session for a shift', async () => {
      const mockSession = {
        session: {
          id: 1,
          status: 'active',
          startedAt: '2024-01-15T09:00:00Z',
        },
      }
      vi.mocked(api.post).mockResolvedValue({ data: mockSession })

      const result = await shiftService.startSession(1)

      expect(api.post).toHaveBeenCalledWith('/shifts/1/session/start')
      expect(result).toEqual(mockSession)
    })

    it('handles session start failure', async () => {
      vi.mocked(api.post).mockRejectedValue({
        response: { status: 400, data: { error: 'Shift not yet started' } },
      })

      await expect(shiftService.startSession(1)).rejects.toMatchObject({
        response: { status: 400 },
      })
    })
  })

  describe('endSession', () => {
    it('ends a session with reason', async () => {
      const mockResponse = { message: 'Session ended' }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await shiftService.endSession(1, 10, 'shift_completed')

      expect(api.post).toHaveBeenCalledWith('/shifts/1/session/10/end', { reason: 'shift_completed' })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('sessionHeartbeat', () => {
    it('sends heartbeat for active session', async () => {
      const mockResponse = { message: 'Heartbeat received' }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await shiftService.sessionHeartbeat(10)

      expect(api.post).toHaveBeenCalledWith('/sessions/10/heartbeat')
      expect(result).toEqual(mockResponse)
    })
  })
})
