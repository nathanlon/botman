<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { clientService } from '@/services/client.service'
import type { Site } from '@/types'

const sites = ref<Site[]>([])
const loading = ref(true)
const showCreateModal = ref(false)
const newSite = ref({
  name: '',
  address: '',
  regionCode: '',
  timezoneId: '',
})

const regions = [
  { code: 'us-east-1', name: 'US East (N. Virginia)' },
  { code: 'us-west-2', name: 'US West (Oregon)' },
  { code: 'eu-west-1', name: 'Europe (Ireland)' },
  { code: 'ap-northeast-1', name: 'Asia Pacific (Tokyo)' },
  { code: 'ap-southeast-1', name: 'Asia Pacific (Singapore)' },
]

onMounted(async () => {
  await loadSites()
})

async function loadSites() {
  loading.value = true
  try {
    const response = await clientService.getSites()
    sites.value = response.sites
  } catch (err) {
    console.error('Failed to load sites', err)
  } finally {
    loading.value = false
  }
}

async function createSite() {
  try {
    await clientService.createSite(newSite.value)
    showCreateModal.value = false
    newSite.value = { name: '', address: '', regionCode: '', timezoneId: '' }
    await loadSites()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to create site')
  }
}
</script>

<template>
  <div class="sites-view">
    <div class="page-header">
      <h1>Sites</h1>
      <button class="btn btn-primary" @click="showCreateModal = true">
        Add Site
      </button>
    </div>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="sites.length === 0" class="no-data">
      <p>No sites configured yet</p>
      <button class="btn btn-primary" @click="showCreateModal = true">
        Create Your First Site
      </button>
    </div>

    <div v-else class="sites-grid">
      <router-link
        v-for="site in sites"
        :key="site.id"
        :to="`/client/sites/${site.id}`"
        class="site-card"
      >
        <h3>{{ site.name }}</h3>
        <p class="address">{{ site.address || 'No address' }}</p>
        <div class="site-meta">
          <span class="region">{{ site.regionCode }}</span>
          <span class="robots">{{ site.robotCount }} robots</span>
        </div>
        <span :class="['status', site.status]">{{ site.status }}</span>
      </router-link>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <h2>Add New Site</h2>
        <form @submit.prevent="createSite">
          <div class="form-group">
            <label>Site Name</label>
            <input v-model="newSite.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea v-model="newSite.address" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Region</label>
            <select v-model="newSite.regionCode" required>
              <option value="">Select region</option>
              <option v-for="r in regions" :key="r.code" :value="r.code">
                {{ r.name }}
              </option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn" @click="showCreateModal = false">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">Create Site</button>
          </div>
        </form>
      </div>
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

.sites-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.site-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  text-decoration: none;
  color: inherit;
  transition: transform 0.2s, box-shadow 0.2s;
}

.site-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.site-card h3 {
  margin: 0 0 5px;
  color: #333;
}

.address {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.site-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.region {
  background: #e3f2fd;
  color: #1976d2;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.85rem;
}

.robots {
  color: #666;
  font-size: 0.9rem;
}

.status {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
}

.status.active {
  background: #e8f5e9;
  color: #2e7d32;
}

.status.inactive {
  background: #f5f5f5;
  color: #666;
}

.btn {
  padding: 10px 20px;
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
  padding: 60px 20px;
  background: white;
  border-radius: 8px;
}

.no-data p {
  margin-bottom: 20px;
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
  max-width: 450px;
}

.modal h2 {
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  color: #666;
}

.form-group input,
.form-group select,
.form-group textarea {
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
