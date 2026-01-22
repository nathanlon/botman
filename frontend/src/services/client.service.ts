import api from './api'
import type { Organization, Site, Robot, Job } from '@/types'

export const clientService = {
  async getOrganization(): Promise<Organization> {
    const response = await api.get('/client/organization')
    return response.data
  },

  async getSites(): Promise<{ sites: Site[] }> {
    const response = await api.get('/client/sites')
    return response.data
  },

  async createSite(data: Partial<Site>): Promise<{ site: Site; message: string }> {
    const response = await api.post('/client/sites', data)
    return response.data
  },

  async getSite(id: number): Promise<Site & { robots: Robot[] }> {
    const response = await api.get(`/client/sites/${id}`)
    return response.data
  },

  async updateSite(id: number, data: Partial<Site>): Promise<{ message: string }> {
    const response = await api.put(`/client/sites/${id}`, data)
    return response.data
  },

  async createRobot(siteId: number, data: Partial<Robot>): Promise<{ robot: Robot; message: string }> {
    const response = await api.post(`/client/sites/${siteId}/robots`, data)
    return response.data
  },

  async getRobot(id: number): Promise<Robot> {
    const response = await api.get(`/client/robots/${id}`)
    return response.data
  },

  async updateRobot(id: number, data: Partial<Robot>): Promise<{ message: string }> {
    const response = await api.put(`/client/robots/${id}`, data)
    return response.data
  },

  async getJobs(): Promise<{ jobs: Job[] }> {
    const response = await api.get('/client/jobs')
    return response.data
  },
}
