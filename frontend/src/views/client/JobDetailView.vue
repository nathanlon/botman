<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { shiftService } from '@/services/shift.service'
import type { Job } from '@/types'

const route = useRoute()
const jobId = Number(route.params.id)
const job = ref<Job | null>(null)
const loading = ref(true)
const activating = ref(false)

onMounted(async () => {
  await loadJob()
})

async function loadJob() {
  loading.value = true
  try {
    job.value = await shiftService.getJob(jobId)
  } catch (err) {
    console.error('Failed to load job', err)
  } finally {
    loading.value = false
  }
}

async function activateJob() {
  if (!confirm('Activate this job? This will generate shifts for operators to accept.')) return
  activating.value = true
  try {
    const response = await shiftService.activateJob(jobId)
    alert(`Job activated! ${response.shiftsGenerated} shifts were generated.`)
    await loadJob()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to activate job')
  } finally {
    activating.value = false
  }
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    draft: '#ff9800',
    open: '#4caf50',
    in_progress: '#2196f3',
    completed: '#9e9e9e',
    cancelled: '#f44336',
    unassigned: '#ff9800',
    assigned: '#2196f3',
  }
  return colors[status] || '#9e9e9e'
}

function formatDateTime(dateStr: string): string {
  return new Date(dateStr).toLocaleString()
}
</script>

<template>
  <div class="job-detail">
    <div v-if="loading" class="loading">Loading...</div>

    <template v-else-if="job">
      <div class="page-header">
        <div>
          <router-link to="/client/jobs" class="back-link">← Back to Jobs</router-link>
          <h1>{{ job.title }}</h1>
          <p class="site">{{ job.site.name }}</p>
        </div>
        <div class="header-actions">
          <span
            class="status-badge"
            :style="{ backgroundColor: getStatusColor(job.status) }"
          >
            {{ job.status }}
          </span>
          <button
            v-if="job.status === 'draft'"
            class="btn btn-success"
            :disabled="activating"
            @click="activateJob"
          >
            {{ activating ? 'Activating...' : 'Activate Job' }}
          </button>
        </div>
      </div>

      <div class="content-grid">
        <div class="card">
          <h2>Job Details</h2>
          <div class="detail-row">
            <label>Description</label>
            <p>{{ job.description || 'No description' }}</p>
          </div>
          <div class="detail-row">
            <label>Schedule</label>
            <p>{{ job.startDate }} to {{ job.endDate }}</p>
          </div>
          <div class="detail-row">
            <label>Hourly Rate</label>
            <p>{{ job.hourlyRateCurrency }} {{ job.hourlyRateAmount }}</p>
          </div>
          <div class="detail-row">
            <label>Max Latency</label>
            <p>{{ job.maxLatencyMs }}ms</p>
          </div>
        </div>

        <div class="card">
          <h2>Robots ({{ job.robots?.length || 0 }})</h2>
          <div v-if="job.robots?.length" class="robot-list">
            <div v-for="robot in job.robots" :key="robot.id" class="robot-item">
              <strong>{{ robot.name }}</strong>
              <span>{{ robot.model }}</span>
            </div>
          </div>
          <p v-else class="no-data">No robots assigned</p>
        </div>

        <div class="card full-width">
          <h2>Shifts ({{ job.shifts?.length || 0 }})</h2>
          <div v-if="job.shifts?.length" class="shifts-table">
            <table>
              <thead>
                <tr>
                  <th>Start Time</th>
                  <th>End Time</th>
                  <th>Status</th>
                  <th>Operator</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="shift in job.shifts" :key="shift.id">
                  <td>{{ formatDateTime(shift.startTimeUtc) }}</td>
                  <td>{{ formatDateTime(shift.endTimeUtc) }}</td>
                  <td>
                    <span
                      class="shift-status"
                      :style="{ color: getStatusColor(shift.status) }"
                    >
                      {{ shift.status }}
                    </span>
                  </td>
                  <td>{{ shift.assignedOperator?.fullName || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="no-data">
            {{ job.status === 'draft' ? 'Activate job to generate shifts' : 'No shifts' }}
          </p>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.back-link {
  color: #2196f3;
  text-decoration: none;
  font-size: 0.9rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 10px 0 5px;
}

.site {
  color: #666;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.status-badge {
  padding: 6px 14px;
  border-radius: 4px;
  color: white;
  text-transform: capitalize;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card.full-width {
  grid-column: 1 / -1;
}

.card h2 {
  font-size: 1.1rem;
  margin: 0 0 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.detail-row {
  margin-bottom: 15px;
}

.detail-row label {
  display: block;
  color: #999;
  font-size: 0.85rem;
  margin-bottom: 3px;
}

.detail-row p {
  margin: 0;
}

.robot-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.robot-item {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  background: #f9f9f9;
  border-radius: 4px;
}

.shifts-table {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  text-align: left;
  padding: 12px;
  border-bottom: 1px solid #eee;
}

th {
  color: #666;
  font-weight: 500;
}

.shift-status {
  font-weight: 500;
  text-transform: capitalize;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-success {
  background: #4caf50;
  color: white;
}

.btn:disabled {
  opacity: 0.5;
}

.no-data {
  color: #999;
  font-style: italic;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
