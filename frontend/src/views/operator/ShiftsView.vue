<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useOperatorStore } from '@/stores/operator'
import { shiftService } from '@/services/shift.service'
import type { Shift, Session } from '@/types'

const operatorStore = useOperatorStore()
const activeSession = ref<Session | null>(null)
const loading = ref(false)
const statusFilter = ref('all')

onMounted(() => {
  operatorStore.fetchShifts()
})

const filteredShifts = computed(() => {
  if (statusFilter.value === 'all') {
    return operatorStore.shifts
  }
  return operatorStore.shifts.filter(s => s.status === statusFilter.value)
})

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString()
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    unassigned: '#ff9800',
    assigned: '#2196f3',
    in_progress: '#4caf50',
    completed: '#9e9e9e',
    missed: '#f44336',
    cancelled: '#9e9e9e',
  }
  return colors[status] || '#9e9e9e'
}

async function startSession(shift: Shift) {
  loading.value = true
  try {
    const response = await shiftService.startSession(shift.id)
    activeSession.value = response.session
    await operatorStore.fetchShifts()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to start session')
  } finally {
    loading.value = false
  }
}

async function endSession(shift: Shift) {
  if (!activeSession.value) return
  loading.value = true
  try {
    await shiftService.endSession(shift.id, activeSession.value.id, 'shift_completed')
    activeSession.value = null
    await operatorStore.fetchShifts()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to end session')
  } finally {
    loading.value = false
  }
}

async function cancelShift(shift: Shift) {
  if (!confirm('Are you sure you want to cancel this shift?')) return
  loading.value = true
  try {
    await shiftService.cancelShift(shift.id, 'Operator cancelled')
    await operatorStore.fetchShifts()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to cancel shift')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="shifts-view">
    <h1>My Shifts</h1>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="all">All Status</option>
        <option value="assigned">Assigned</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div v-if="operatorStore.loading" class="loading">Loading...</div>

    <div v-else-if="filteredShifts.length === 0" class="no-shifts">
      <p>No shifts found</p>
      <router-link to="/operator/marketplace" class="btn">
        Find Shifts in Marketplace
      </router-link>
    </div>

    <div v-else class="shifts-list">
      <div v-for="shift in filteredShifts" :key="shift.id" class="shift-card">
        <div class="shift-header">
          <h3>{{ shift.job.title }}</h3>
          <span
            class="status-badge"
            :style="{ backgroundColor: getStatusColor(shift.status) }"
          >
            {{ shift.status }}
          </span>
        </div>

        <div class="shift-details">
          <p><strong>Site:</strong> {{ shift.site.name }}</p>
          <p><strong>Start:</strong> {{ formatDate(shift.startTimeUtc) }}</p>
          <p><strong>End:</strong> {{ formatDate(shift.endTimeUtc) }}</p>
          <p><strong>Duration:</strong> {{ shift.durationMinutes }} minutes</p>
          <p><strong>Rate:</strong> {{ shift.job.currency }} {{ shift.job.hourlyRate }}/hr</p>
        </div>

        <div class="shift-actions">
          <button
            v-if="shift.status === 'assigned'"
            class="btn btn-success"
            :disabled="loading"
            @click="startSession(shift)"
          >
            Start Session
          </button>
          <button
            v-if="shift.status === 'in_progress'"
            class="btn btn-warning"
            :disabled="loading"
            @click="endSession(shift)"
          >
            End Session
          </button>
          <button
            v-if="shift.status === 'assigned'"
            class="btn btn-danger"
            :disabled="loading"
            @click="cancelShift(shift)"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shifts-view h1 {
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

.no-shifts {
  text-align: center;
  padding: 40px;
  background: white;
  border-radius: 8px;
}

.shifts-list {
  display: grid;
  gap: 15px;
}

.shift-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.shift-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.shift-header h3 {
  margin: 0;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  color: white;
  font-size: 0.85rem;
  text-transform: capitalize;
}

.shift-details {
  margin-bottom: 15px;
}

.shift-details p {
  margin: 5px 0;
  color: #666;
}

.shift-actions {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
}

.btn-success {
  background: #4caf50;
  color: white;
}

.btn-warning {
  background: #ff9800;
  color: white;
}

.btn-danger {
  background: #f44336;
  color: white;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
