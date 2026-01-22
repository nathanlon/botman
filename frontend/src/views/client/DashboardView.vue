<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { clientService } from '@/services/client.service'
import type { Site, Job, ClientUser } from '@/types'

const authStore = useAuthStore()
const sites = ref<Site[]>([])
const jobs = ref<Job[]>([])
const loading = ref(true)

const user = authStore.user as ClientUser

onMounted(async () => {
  try {
    const [sitesRes, jobsRes] = await Promise.all([
      clientService.getSites(),
      clientService.getJobs(),
    ])
    sites.value = sitesRes.sites
    jobs.value = jobsRes.jobs
  } catch (err) {
    console.error('Failed to load dashboard', err)
  } finally {
    loading.value = false
  }
})

const totalRobots = () => sites.value.reduce((sum, s) => sum + (s.robotCount || 0), 0)
const activeJobs = () => jobs.value.filter(j => j.status === 'open' || j.status === 'in_progress').length
</script>

<template>
  <div class="client-dashboard">
    <h1>{{ user?.organization?.companyName }}</h1>
    <p class="subtitle">Welcome, {{ user?.fullName }}</p>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else class="dashboard-grid">
      <div class="stats-row">
        <div class="stat-card">
          <h3>{{ sites.length }}</h3>
          <p>Sites</p>
        </div>
        <div class="stat-card">
          <h3>{{ totalRobots() }}</h3>
          <p>Robots</p>
        </div>
        <div class="stat-card">
          <h3>{{ activeJobs() }}</h3>
          <p>Active Jobs</p>
        </div>
        <div class="stat-card">
          <h3>{{ jobs.length }}</h3>
          <p>Total Jobs</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Sites</h2>
          <router-link to="/client/sites" class="link">View All</router-link>
        </div>
        <div v-if="sites.length" class="list">
          <div v-for="site in sites.slice(0, 5)" :key="site.id" class="list-item">
            <div>
              <strong>{{ site.name }}</strong>
              <span class="meta">{{ site.regionCode }} - {{ site.robotCount }} robots</span>
            </div>
            <span :class="['status', site.status]">{{ site.status }}</span>
          </div>
        </div>
        <p v-else class="no-data">No sites configured</p>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Recent Jobs</h2>
          <router-link to="/client/jobs" class="link">View All</router-link>
        </div>
        <div v-if="jobs.length" class="list">
          <div v-for="job in jobs.slice(0, 5)" :key="job.id" class="list-item">
            <div>
              <strong>{{ job.title }}</strong>
              <span class="meta">{{ job.site.name }} - {{ job.shiftCount }} shifts</span>
            </div>
            <span :class="['status', job.status]">{{ job.status }}</span>
          </div>
        </div>
        <p v-else class="no-data">No jobs created</p>
      </div>

      <div class="card quick-actions">
        <h2>Quick Actions</h2>
        <router-link to="/client/sites" class="action-btn">Manage Sites</router-link>
        <router-link to="/client/jobs/new" class="action-btn primary">Create New Job</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.client-dashboard h1 {
  margin-bottom: 5px;
}

.subtitle {
  color: #666;
  margin-bottom: 30px;
}

.dashboard-grid {
  display: grid;
  gap: 20px;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
  font-size: 2rem;
  color: #2196f3;
  margin: 0;
}

.stat-card p {
  color: #666;
  margin: 5px 0 0;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.card-header h2 {
  margin: 0;
  font-size: 1.1rem;
}

.link {
  color: #2196f3;
  text-decoration: none;
  font-size: 0.9rem;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.list-item:last-child {
  border-bottom: none;
}

.meta {
  display: block;
  color: #999;
  font-size: 0.85rem;
}

.status {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  text-transform: capitalize;
}

.status.active, .status.open {
  background: #e8f5e9;
  color: #2e7d32;
}

.status.draft {
  background: #fff3e0;
  color: #f57c00;
}

.status.in_progress {
  background: #e3f2fd;
  color: #1976d2;
}

.no-data {
  color: #999;
  font-style: italic;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quick-actions h2 {
  margin-bottom: 10px;
}

.action-btn {
  padding: 12px;
  text-align: center;
  text-decoration: none;
  border-radius: 4px;
  background: #f5f5f5;
  color: #333;
}

.action-btn.primary {
  background: #2196f3;
  color: white;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
