import { describe, it, expect, beforeEach, vi } from 'vitest'
import { clientService } from '@/services/client.service'
import api from '@/services/api'

vi.mock('@/services/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('Client Service', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getOrganization', () => {
    it('fetches organization details', async () => {
      const mockOrg = {
        organization: {
          id: 1,
          companyName: 'Test Corp',
          status: 'active',
        },
      }
      vi.mocked(api.get).mockResolvedValue({ data: mockOrg })

      const result = await clientService.getOrganization()

      expect(api.get).toHaveBeenCalledWith('/client/organization')
      expect(result).toEqual(mockOrg)
    })
  })

  describe('updateOrganization', () => {
    it('updates organization details', async () => {
      const mockResponse = { message: 'Updated' }
      vi.mocked(api.put).mockResolvedValue({ data: mockResponse })

      const result = await clientService.updateOrganization({
        companyName: 'New Name',
        billingEmail: 'billing@test.com',
      })

      expect(api.put).toHaveBeenCalledWith('/client/organization', {
        companyName: 'New Name',
        billingEmail: 'billing@test.com',
      })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('Sites management', () => {
    it('fetches all sites', async () => {
      const mockSites = {
        sites: [
          { id: 1, name: 'Site A', regionCode: 'US-CA' },
          { id: 2, name: 'Site B', regionCode: 'US-NY' },
        ],
      }
      vi.mocked(api.get).mockResolvedValue({ data: mockSites })

      const result = await clientService.getSites()

      expect(api.get).toHaveBeenCalledWith('/client/sites')
      expect(result).toEqual(mockSites)
    })

    it('fetches single site with robots', async () => {
      const mockSite = {
        id: 1,
        name: 'Site A',
        robots: [
          { id: 1, name: 'Robot-001', status: 'online' },
        ],
      }
      vi.mocked(api.get).mockResolvedValue({ data: { site: mockSite } })

      const result = await clientService.getSite(1)

      expect(api.get).toHaveBeenCalledWith('/client/sites/1')
      expect(result).toEqual(mockSite)
    })

    it('creates a new site', async () => {
      const mockResponse = {
        site: { id: 1, name: 'New Site', regionCode: 'US-TX', status: 'active' },
      }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await clientService.createSite({
        name: 'New Site',
        regionCode: 'US-TX',
        address: '123 Main St',
      })

      expect(api.post).toHaveBeenCalledWith('/client/sites', {
        name: 'New Site',
        regionCode: 'US-TX',
        address: '123 Main St',
      })
      expect(result).toEqual(mockResponse)
    })

    it('updates a site', async () => {
      const mockResponse = { message: 'Updated' }
      vi.mocked(api.put).mockResolvedValue({ data: mockResponse })

      const result = await clientService.updateSite(1, { name: 'Updated Name' })

      expect(api.put).toHaveBeenCalledWith('/client/sites/1', { name: 'Updated Name' })
      expect(result).toEqual(mockResponse)
    })

    it('deactivates a site', async () => {
      const mockResponse = { message: 'Deactivated' }
      vi.mocked(api.delete).mockResolvedValue({ data: mockResponse })

      const result = await clientService.deleteSite(1)

      expect(api.delete).toHaveBeenCalledWith('/client/sites/1')
      expect(result).toEqual(mockResponse)
    })
  })

  describe('Robots management', () => {
    it('creates a robot at a site', async () => {
      const mockResponse = {
        robot: { id: 1, name: 'Robot-001', model: 'Universal-UR10', status: 'offline' },
      }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await clientService.createRobot(1, {
        name: 'Robot-001',
        model: 'Universal-UR10',
        connectionEndpoint: 'wss://robot.example.com',
      })

      expect(api.post).toHaveBeenCalledWith('/client/sites/1/robots', {
        name: 'Robot-001',
        model: 'Universal-UR10',
        connectionEndpoint: 'wss://robot.example.com',
      })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('Jobs management', () => {
    it('fetches all jobs', async () => {
      const mockJobs = {
        jobs: [
          { id: 1, title: 'Job A', status: 'active' },
          { id: 2, title: 'Job B', status: 'draft' },
        ],
      }
      vi.mocked(api.get).mockResolvedValue({ data: mockJobs })

      const result = await clientService.getJobs()

      expect(api.get).toHaveBeenCalledWith('/client/jobs')
      expect(result).toEqual(mockJobs)
    })

    it('fetches single job with shifts', async () => {
      const mockJob = {
        id: 1,
        title: 'Job A',
        shifts: [{ id: 1, status: 'unassigned' }],
      }
      vi.mocked(api.get).mockResolvedValue({ data: { job: mockJob } })

      const result = await clientService.getJob(1)

      expect(api.get).toHaveBeenCalledWith('/client/jobs/1')
      expect(result).toEqual(mockJob)
    })

    it('creates a new job', async () => {
      const mockResponse = {
        job: { id: 1, title: 'New Job', status: 'draft' },
      }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const jobData = {
        title: 'New Job',
        siteId: 1,
        startDate: '2024-01-15',
        endDate: '2024-01-22',
        hourlyRateAmount: 25,
        hourlyRateCurrency: 'USD',
        maxLatencyMs: 150,
      }

      const result = await clientService.createJob(jobData)

      expect(api.post).toHaveBeenCalledWith('/client/jobs', jobData)
      expect(result).toEqual(mockResponse)
    })

    it('activates a job', async () => {
      const mockResponse = { message: 'Activated' }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await clientService.activateJob(1)

      expect(api.post).toHaveBeenCalledWith('/client/jobs/1/activate')
      expect(result).toEqual(mockResponse)
    })

    it('pauses a job', async () => {
      const mockResponse = { message: 'Paused' }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const result = await clientService.pauseJob(1)

      expect(api.post).toHaveBeenCalledWith('/client/jobs/1/pause')
      expect(result).toEqual(mockResponse)
    })

    it('cancels a job', async () => {
      const mockResponse = { message: 'Cancelled' }
      vi.mocked(api.delete).mockResolvedValue({ data: mockResponse })

      const result = await clientService.cancelJob(1)

      expect(api.delete).toHaveBeenCalledWith('/client/jobs/1')
      expect(result).toEqual(mockResponse)
    })

    it('adds a shift to a job', async () => {
      const mockResponse = {
        shift: { id: 1, status: 'unassigned' },
      }
      vi.mocked(api.post).mockResolvedValue({ data: mockResponse })

      const shiftData = {
        startTimeUtc: '2024-01-15T09:00:00Z',
        endTimeUtc: '2024-01-15T17:00:00Z',
        robotId: 1,
      }

      const result = await clientService.addShift(1, shiftData)

      expect(api.post).toHaveBeenCalledWith('/client/jobs/1/shifts', shiftData)
      expect(result).toEqual(mockResponse)
    })
  })
})
