<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useOperatorStore } from '@/stores/operator'
import type { Availability } from '@/types'

const operatorStore = useOperatorStore()
const editMode = ref(false)
const editSlots = ref<Availability[]>([])

const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

onMounted(async () => {
  await operatorStore.fetchAvailability()
})

function startEdit() {
  editSlots.value = JSON.parse(JSON.stringify(operatorStore.availability))
  editMode.value = true
}

function cancelEdit() {
  editMode.value = false
  editSlots.value = []
}

function addSlot() {
  editSlots.value.push({
    id: 0,
    dayOfWeek: 1,
    startTime: '09:00',
    endTime: '17:00',
  })
}

function removeSlot(index: number) {
  editSlots.value.splice(index, 1)
}

async function saveAvailability() {
  try {
    await operatorStore.setAvailability(editSlots.value)
    editMode.value = false
  } catch (err) {
    // Error handled in store
  }
}

const groupedAvailability = computed(() => {
  const grouped: Record<number, Availability[]> = {}
  for (let i = 0; i < 7; i++) {
    grouped[i] = operatorStore.availability.filter(a => a.dayOfWeek === i)
  }
  return grouped
})
</script>

<template>
  <div class="availability-view">
    <div class="page-header">
      <h1>My Availability</h1>
      <button v-if="!editMode" class="btn btn-primary" @click="startEdit">
        Edit Availability
      </button>
    </div>

    <div v-if="operatorStore.loading" class="loading">Loading...</div>

    <!-- View Mode -->
    <div v-else-if="!editMode" class="availability-grid">
      <div v-for="(daySlots, dayIndex) in groupedAvailability" :key="dayIndex" class="day-card">
        <h3>{{ days[dayIndex] }}</h3>
        <div v-if="daySlots.length" class="time-slots">
          <div v-for="slot in daySlots" :key="slot.id" class="time-slot">
            {{ slot.startTime }} - {{ slot.endTime }}
          </div>
        </div>
        <p v-else class="unavailable">Not available</p>
      </div>
    </div>

    <!-- Edit Mode -->
    <div v-else class="edit-mode">
      <div class="edit-header">
        <h2>Edit Weekly Availability</h2>
        <button class="btn" @click="addSlot">+ Add Time Slot</button>
      </div>

      <div v-if="editSlots.length === 0" class="no-slots">
        <p>No availability set. Add time slots when you're available to work.</p>
      </div>

      <div v-else class="slots-list">
        <div v-for="(slot, index) in editSlots" :key="index" class="slot-row">
          <select v-model.number="slot.dayOfWeek">
            <option v-for="(day, i) in days" :key="i" :value="i">{{ day }}</option>
          </select>
          <input v-model="slot.startTime" type="time" />
          <span>to</span>
          <input v-model="slot.endTime" type="time" />
          <button class="remove-btn" @click="removeSlot(index)">×</button>
        </div>
      </div>

      <div class="edit-actions">
        <button class="btn" @click="cancelEdit">Cancel</button>
        <button class="btn btn-primary" @click="saveAvailability">Save Changes</button>
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

.availability-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 15px;
}

.day-card {
  background: white;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.day-card h3 {
  font-size: 0.95rem;
  margin: 0 0 10px;
  color: #333;
}

.time-slots {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.time-slot {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 0.9rem;
}

.unavailable {
  color: #999;
  font-size: 0.9rem;
  font-style: italic;
}

.edit-mode {
  background: white;
  padding: 20px;
  border-radius: 8px;
}

.edit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.edit-header h2 {
  margin: 0;
}

.no-slots {
  text-align: center;
  padding: 40px;
  color: #666;
}

.slots-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}

.slot-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: #f9f9f9;
  border-radius: 4px;
}

.slot-row select,
.slot-row input {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.slot-row select {
  min-width: 120px;
}

.slot-row input[type="time"] {
  width: 110px;
}

.remove-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #999;
  cursor: pointer;
  padding: 0 10px;
}

.remove-btn:hover {
  color: #f44336;
}

.edit-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding-top: 20px;
  border-top: 1px solid #eee;
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

.loading {
  text-align: center;
  padding: 40px;
}
</style>
