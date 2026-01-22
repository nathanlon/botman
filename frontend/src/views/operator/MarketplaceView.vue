<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { shiftService } from '@/services/shift.service'
import type { Job, Shift } from '@/types'

const jobs = ref<Job[]>([])
const availableShifts = ref<Shift[]>([])
const loading = ref(false)
const viewMode = ref<'jobs' | 'shifts'>('jobs')
const selectedJob = ref<Job | null>(null)

onMounted(async () => {
  loading.value = true
  try {
    const [jobsResponse, shiftsResponse] = await Promise.all([
      shiftService.getMarketplace(),
      shiftService.getAvailableShifts(),
    ])
    jobs.value = jobsResponse.jobs
    availableShifts.value = shiftsResponse.shifts
  } catch (err) {
    console.error('Failed to load marketplace', err)
  } finally {
    loading.value = false
  }
})

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString()
}

function formatDateTime(dateStr: string): string {
  return new Date(dateStr).toLocaleString()
}

async function acceptShift(shift: Shift) {
  if (!confirm('Accept this shift?')) return
  loading.value = true
  try {
    await shiftService.acceptShift(shift.id)
    // Refresh shifts
    const response = await shiftService.getAvailableShifts()
    availableShifts.value = response.shifts
    alert('Shift accepted successfully!')
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to accept shift')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="marketplace-view">
    <h1>Job Marketplace</h1>

    <div class="view-toggle">
      <button :class="{ active: viewMode === 'jobs' }" @click="viewMode = 'jobs'">
        View Jobs
      </button>
      <button :class="{ active: viewMode === 'shifts' }" @click="viewMode = 'shifts'">
        View Available Shifts
      </button>
    </div>

    <div v-if="loading" class="loading">Loading...</div>

    <!-- Jobs View -->
    <div v-else-if="viewMode === 'jobs'" class="jobs-grid">
      <div v-for="job in jobs" :key="job.id" class="job-card">
        <h3>{{ job.title }}</h3>
        <p class="company">{{ job.organization?.companyName }}</p>
        <p class="site">{{ job.site.name }} ({{ job.site.regionCode }})</p>

        <div class="job-meta">
          <span>{{ formatDate(job.startDate) }} - {{ formatDate(job.endDate) }}</span>
          <span class="rate">{{ job.hourlyRateCurrency }} {{ job.hourlyRateAmount }}/hr</span>
        </div>

        <p v-if="job.description" class="description">{{ job.description }}</p>

        <div class="job-stats">
          <span class="available-shifts">
            {{ job.availableShifts }} shifts available
          </span>
          <span class="latency">Max latency: {{ job.maxLatencyMs }}ms</span>
        </div>

        <button
          class="btn"
          @click="viewMode = 'shifts'"
        >
          View Shifts
        </button>
      </div>

      <div v-if="jobs.length === 0" class="no-data">
        No open jobs at the moment
      </div>
    </div>

    <!-- Shifts View -->
    <div v-else class="shifts-grid">
      <div v-for="shift in availableShifts" :key="shift.id" class="shift-card">
        <h4>{{ shift.job.title }}</h4>
        <p class="site">{{ shift.site.name }}</p>

        <div class="shift-time">
          <p><strong>Start:</strong> {{ formatDateTime(shift.startTimeUtc) }}</p>
          <p><strong>End:</strong> {{ formatDateTime(shift.endTimeUtc) }}</p>
          <p><strong>Duration:</strong> {{ shift.durationMinutes }} min</p>
        </div>

        <p class="rate">
          {{ shift.job.currency }} {{ shift.job.hourlyRate }}/hr
        </p>

        <button
          class="btn btn-accept"
          :disabled="loading"
          @click="acceptShift(shift)"
        >
          Accept Shift
        </button>
      </div>

      <div v-if="availableShifts.length === 0" class="no-data">
        No available shifts at the moment
      </div>
    </div>
  </div>
</template>

<style scoped>
.marketplace-view h1 {
  margin-bottom: 20px;
}

.view-toggle {
  display: flex;
  margin-bottom: 20px;
  background: #f5f5f5;
  border-radius: 4px;
  padding: 4px;
}

.view-toggle button {
  flex: 1;
  padding: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 4px;
}

.view-toggle button.active {
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.jobs-grid, .shifts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.job-card, .shift-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.job-card h3 {
  margin: 0 0 5px;
}

.company {
  color: #2196f3;
  font-weight: 500;
  margin-bottom: 5px;
}

.site {
  color: #666;
  margin-bottom: 10px;
}

.job-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 0.9rem;
  color: #666;
}

.rate {
  color: #4caf50;
  font-weight: 600;
}

.description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.job-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 15px;
}

.available-shifts {
  color: #ff9800;
}

.latency {
  color: #666;
}

.shift-time {
  margin: 10px 0;
}

.shift-time p {
  margin: 3px 0;
  font-size: 0.9rem;
}

.btn {
  width: 100%;
  padding: 10px;
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-accept {
  background: #4caf50;
}

.btn:disabled {
  opacity: 0.5;
}

.no-data {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
  color: #666;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
