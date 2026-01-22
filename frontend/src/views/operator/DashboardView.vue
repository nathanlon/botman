<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useOperatorStore } from '@/stores/operator'

const authStore = useAuthStore()
const operatorStore = useOperatorStore()

onMounted(() => {
  operatorStore.fetchShifts()
  operatorStore.fetchSkills()
})

const upcomingShifts = computed(() => {
  const now = new Date()
  return operatorStore.shifts
    .filter(s => new Date(s.startTimeUtc) > now && s.status === 'assigned')
    .slice(0, 5)
})

const activeShift = computed(() => {
  return operatorStore.shifts.find(s => s.status === 'in_progress')
})

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString()
}
</script>

<template>
  <div class="operator-dashboard">
    <h1>Welcome, {{ authStore.user?.fullName }}</h1>

    <div class="dashboard-grid">
      <div class="card">
        <h2>Active Session</h2>
        <div v-if="activeShift" class="active-session">
          <p class="job-title">{{ activeShift.job.title }}</p>
          <p>{{ activeShift.site.name }}</p>
          <p class="time">Started: {{ formatDate(activeShift.startTimeUtc) }}</p>
          <router-link :to="`/operator/shifts`" class="btn">
            View Session
          </router-link>
        </div>
        <p v-else class="no-data">No active session</p>
      </div>

      <div class="card">
        <h2>Upcoming Shifts</h2>
        <div v-if="upcomingShifts.length" class="shift-list">
          <div v-for="shift in upcomingShifts" :key="shift.id" class="shift-item">
            <strong>{{ shift.job.title }}</strong>
            <span class="time">{{ formatDate(shift.startTimeUtc) }}</span>
          </div>
        </div>
        <p v-else class="no-data">No upcoming shifts</p>
        <router-link to="/operator/marketplace" class="btn btn-outline">
          Find More Shifts
        </router-link>
      </div>

      <div class="card">
        <h2>My Skills</h2>
        <div v-if="operatorStore.skills.length" class="skill-badges">
          <span v-for="skill in operatorStore.skills" :key="skill.id" class="badge">
            {{ skill.skillName }} ({{ skill.proficiencyLevel }}/5)
          </span>
        </div>
        <p v-else class="no-data">No skills added</p>
        <router-link to="/operator/skills" class="btn btn-outline">
          Manage Skills
        </router-link>
      </div>

      <div class="card">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
          <router-link to="/operator/availability" class="action-btn">
            Set Availability
          </router-link>
          <router-link to="/operator/marketplace" class="action-btn">
            Browse Jobs
          </router-link>
          <router-link to="/operator/shifts" class="action-btn">
            My Shifts
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.operator-dashboard h1 {
  margin-bottom: 30px;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card h2 {
  font-size: 1.1rem;
  color: #333;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.no-data {
  color: #999;
  font-style: italic;
}

.shift-list {
  margin-bottom: 15px;
}

.shift-item {
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.time {
  color: #666;
  font-size: 0.9rem;
}

.skill-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 15px;
}

.badge {
  background: #e3f2fd;
  color: #1976d2;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.85rem;
}

.btn {
  display: inline-block;
  padding: 8px 16px;
  background: #2196f3;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  margin-top: 10px;
}

.btn-outline {
  background: transparent;
  border: 1px solid #2196f3;
  color: #2196f3;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.action-btn {
  padding: 12px;
  background: #f5f5f5;
  color: #333;
  text-decoration: none;
  border-radius: 4px;
  text-align: center;
  transition: background 0.2s;
}

.action-btn:hover {
  background: #e0e0e0;
}

.active-session {
  background: #e8f5e9;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.job-title {
  font-weight: 600;
  color: #2e7d32;
}
</style>
