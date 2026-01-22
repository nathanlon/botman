<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { clientService } from '@/services/client.service'
import { shiftService } from '@/services/shift.service'
import type { Site, Robot } from '@/types'

const router = useRouter()
const sites = ref<Site[]>([])
const selectedSite = ref<(Site & { robots: Robot[] }) | null>(null)
const loading = ref(false)

const form = ref({
  title: '',
  description: '',
  siteId: 0,
  robotIds: [] as number[],
  startDate: '',
  endDate: '',
  hourlyRateAmount: '',
  hourlyRateCurrency: 'USD',
  maxLatencyMs: 150,
})

onMounted(async () => {
  const response = await clientService.getSites()
  sites.value = response.sites
})

async function onSiteChange() {
  if (form.value.siteId) {
    selectedSite.value = await clientService.getSite(form.value.siteId)
    form.value.robotIds = []
  } else {
    selectedSite.value = null
  }
}

async function handleSubmit() {
  loading.value = true
  try {
    const response = await shiftService.createJob(form.value)
    router.push(`/client/jobs/${response.job.id}`)
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to create job')
  } finally {
    loading.value = false
  }
}

const minDate = computed(() => {
  const date = new Date()
  date.setDate(date.getDate() + 1)
  return date.toISOString().split('T')[0]
})
</script>

<template>
  <div class="job-create">
    <router-link to="/client/jobs" class="back-link">← Back to Jobs</router-link>
    <h1>Create New Job</h1>

    <form class="job-form" @submit.prevent="handleSubmit">
      <div class="form-section">
        <h2>Basic Information</h2>

        <div class="form-group">
          <label>Job Title *</label>
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="e.g., Warehouse Robot Operations"
          />
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Describe the job requirements..."
          ></textarea>
        </div>
      </div>

      <div class="form-section">
        <h2>Location & Robots</h2>

        <div class="form-group">
          <label>Site *</label>
          <select v-model="form.siteId" required @change="onSiteChange">
            <option :value="0">Select a site</option>
            <option v-for="site in sites" :key="site.id" :value="site.id">
              {{ site.name }} ({{ site.regionCode }})
            </option>
          </select>
        </div>

        <div v-if="selectedSite" class="form-group">
          <label>Robots</label>
          <div class="checkbox-group">
            <label v-for="robot in selectedSite.robots" :key="robot.id" class="checkbox-item">
              <input
                type="checkbox"
                :value="robot.id"
                v-model="form.robotIds"
              />
              {{ robot.name }} ({{ robot.model }})
            </label>
          </div>
          <small v-if="selectedSite.robots.length === 0">
            No robots at this site. <router-link :to="`/client/sites/${selectedSite.id}`">Add robots</router-link>
          </small>
        </div>
      </div>

      <div class="form-section">
        <h2>Schedule</h2>

        <div class="form-row">
          <div class="form-group">
            <label>Start Date *</label>
            <input v-model="form.startDate" type="date" :min="minDate" required />
          </div>
          <div class="form-group">
            <label>End Date *</label>
            <input v-model="form.endDate" type="date" :min="form.startDate || minDate" required />
          </div>
        </div>
      </div>

      <div class="form-section">
        <h2>Compensation & Requirements</h2>

        <div class="form-row">
          <div class="form-group">
            <label>Hourly Rate *</label>
            <input
              v-model="form.hourlyRateAmount"
              type="number"
              step="0.01"
              min="0"
              required
              placeholder="25.00"
            />
          </div>
          <div class="form-group">
            <label>Currency</label>
            <select v-model="form.hourlyRateCurrency">
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Maximum Latency (ms)</label>
          <input v-model.number="form.maxLatencyMs" type="number" min="50" max="500" />
          <small>Operators must have latency below this threshold</small>
        </div>
      </div>

      <div class="form-actions">
        <router-link to="/client/jobs" class="btn">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="loading">
          {{ loading ? 'Creating...' : 'Create Job' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.back-link {
  color: #2196f3;
  text-decoration: none;
  font-size: 0.9rem;
}

h1 {
  margin: 10px 0 30px;
}

.job-form {
  max-width: 700px;
}

.form-section {
  background: white;
  padding: 25px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.form-section h2 {
  font-size: 1.1rem;
  margin: 0 0 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.form-group small {
  display: block;
  margin-top: 5px;
  color: #999;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-item input {
  width: auto;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  background: #f5f5f5;
}

.btn-primary {
  background: #2196f3;
  color: white;
}

.btn:disabled {
  opacity: 0.5;
}
</style>
