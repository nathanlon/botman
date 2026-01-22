<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { clientService } from '@/services/client.service'
import type { Site, Robot } from '@/types'

const route = useRoute()
const siteId = Number(route.params.id)
const site = ref<(Site & { robots: Robot[] }) | null>(null)
const loading = ref(true)
const showAddRobotModal = ref(false)
const newRobot = ref({ name: '', model: '', connectionEndpoint: '' })

const robotModels = [
  'OpenArm-7DOF',
  'ExoArm-7',
  'Agility-A1',
  'Universal-UR10',
  'Franka-Emika',
]

onMounted(async () => {
  await loadSite()
})

async function loadSite() {
  loading.value = true
  try {
    site.value = await clientService.getSite(siteId)
  } catch (err) {
    console.error('Failed to load site', err)
  } finally {
    loading.value = false
  }
}

async function addRobot() {
  try {
    await clientService.createRobot(siteId, newRobot.value)
    showAddRobotModal.value = false
    newRobot.value = { name: '', model: '', connectionEndpoint: '' }
    await loadSite()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to add robot')
  }
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    offline: '#9e9e9e',
    online: '#4caf50',
    in_session: '#2196f3',
    maintenance: '#ff9800',
    error: '#f44336',
  }
  return colors[status] || '#9e9e9e'
}
</script>

<template>
  <div class="site-detail">
    <div v-if="loading" class="loading">Loading...</div>

    <template v-else-if="site">
      <div class="page-header">
        <div>
          <router-link to="/client/sites" class="back-link">← Back to Sites</router-link>
          <h1>{{ site.name }}</h1>
          <p class="subtitle">{{ site.address || 'No address' }}</p>
        </div>
        <span :class="['status', site.status]">{{ site.status }}</span>
      </div>

      <div class="site-info">
        <div class="info-item">
          <label>Region</label>
          <span>{{ site.regionCode }}</span>
        </div>
        <div class="info-item">
          <label>Timezone</label>
          <span>{{ site.timezoneId || 'Not set' }}</span>
        </div>
        <div class="info-item">
          <label>Total Robots</label>
          <span>{{ site.robots.length }}</span>
        </div>
      </div>

      <div class="robots-section">
        <div class="section-header">
          <h2>Robots</h2>
          <button class="btn btn-primary" @click="showAddRobotModal = true">
            Add Robot
          </button>
        </div>

        <div v-if="site.robots.length === 0" class="no-data">
          No robots added to this site yet
        </div>

        <div v-else class="robots-grid">
          <div v-for="robot in site.robots" :key="robot.id" class="robot-card">
            <div class="robot-header">
              <h3>{{ robot.name }}</h3>
              <span
                class="status-dot"
                :style="{ backgroundColor: getStatusColor(robot.status) }"
                :title="robot.status"
              ></span>
            </div>
            <p class="model">{{ robot.model }}</p>
            <p class="status-text">Status: {{ robot.status }}</p>
          </div>
        </div>
      </div>
    </template>

    <!-- Add Robot Modal -->
    <div v-if="showAddRobotModal" class="modal-overlay" @click.self="showAddRobotModal = false">
      <div class="modal">
        <h2>Add Robot</h2>
        <form @submit.prevent="addRobot">
          <div class="form-group">
            <label>Robot Name</label>
            <input v-model="newRobot.name" type="text" required placeholder="e.g., Robot-A1" />
          </div>
          <div class="form-group">
            <label>Model</label>
            <select v-model="newRobot.model" required>
              <option value="">Select model</option>
              <option v-for="m in robotModels" :key="m" :value="m">{{ m }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Connection Endpoint (optional)</label>
            <input v-model="newRobot.connectionEndpoint" type="text" placeholder="wss://..." />
          </div>
          <div class="modal-actions">
            <button type="button" class="btn" @click="showAddRobotModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Robot</button>
          </div>
        </form>
      </div>
    </div>
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

.subtitle {
  color: #666;
}

.status {
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 0.9rem;
}

.status.active {
  background: #e8f5e9;
  color: #2e7d32;
}

.site-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.info-item label {
  display: block;
  color: #999;
  font-size: 0.85rem;
  margin-bottom: 5px;
}

.info-item span {
  font-weight: 500;
}

.robots-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0;
}

.robots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
}

.robot-card {
  border: 1px solid #eee;
  padding: 15px;
  border-radius: 4px;
}

.robot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.robot-header h3 {
  margin: 0;
  font-size: 1rem;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.model {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 5px;
}

.status-text {
  color: #999;
  font-size: 0.85rem;
  text-transform: capitalize;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  background: #f5f5f5;
}

.btn-primary {
  background: #2196f3;
  color: white;
}

.no-data {
  text-align: center;
  padding: 40px;
  color: #666;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal {
  background: white;
  padding: 30px;
  border-radius: 8px;
  width: 100%;
  max-width: 400px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
