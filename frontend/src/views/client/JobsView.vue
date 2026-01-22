<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { clientService } from '@/services/client.service'
import type { Job } from '@/types'

const jobs = ref<Job[]>([])
const loading = ref(true)
const statusFilter = ref('all')

onMounted(async () => {
  try {
    const response = await clientService.getJobs()
    jobs.value = response.jobs
  } catch (err) {
    console.error('Failed to load jobs', err)
  } finally {
    loading.value = false
  }
})

const filteredJobs = computed(() => {
  if (statusFilter.value === 'all') return jobs.value
  return jobs.value.filter(j => j.status === statusFilter.value)
})

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    draft: '#ff9800',
    open: '#4caf50',
    in_progress: '#2196f3',
    completed: '#9e9e9e',
    cancelled: '#f44336',
  }
  return colors[status] || '#9e9e9e'
}
</script>

<template>
  <div class="jobs-view">
    <div class="page-header">
      <h1>Jobs</h1>
      <router-link to="/client/jobs/new" class="btn btn-primary">
        Create Job
      </router-link>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="all">All Status</option>
        <option value="draft">Draft</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="filteredJobs.length === 0" class="no-data">
      <p>No jobs found</p>
      <router-link to="/client/jobs/new" class="btn btn-primary">
        Create Your First Job
      </router-link>
    </div>

    <div v-else class="jobs-list">
      <router-link
        v-for="job in filteredJobs"
        :key="job.id"
        :to="`/client/jobs/${job.id}`"
        class="job-card"
      >
        <div class="job-header">
          <h3>{{ job.title }}</h3>
          <span
            class="status-badge"
            :style="{ backgroundColor: getStatusColor(job.status) }"
          >
            {{ job.status }}
          </span>
        </div>

        <p class="site">{{ job.site.name }}</p>

        <div class="job-meta">
          <span>{{ job.startDate }} - {{ job.endDate }}</span>
          <span class="rate">{{ job.currency }} {{ job.hourlyRate }}/hr</span>
        </div>

        <div class="job-stats">
          <span>{{ job.shiftCount }} shifts</span>
        </div>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.filters {
  margin-bottom: 20px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.jobs-list {
  display: grid;
  gap: 15px;
}

.job-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.2s;
}

.job-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.job-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.job-header h3 {
  margin: 0;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  color: white;
  font-size: 0.85rem;
  text-transform: capitalize;
}

.site {
  color: #666;
  margin-bottom: 10px;
}

.job-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: #666;
}

.rate {
  color: #4caf50;
  font-weight: 600;
}

.job-stats {
  margin-top: 10px;
  font-size: 0.85rem;
  color: #999;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background: #2196f3;
  color: white;
}

.no-data {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 8px;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
