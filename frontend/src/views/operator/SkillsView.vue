<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useOperatorStore } from '@/stores/operator'
import api from '@/services/api'
import type { SkillDefinition } from '@/types'

const operatorStore = useOperatorStore()
const availableSkills = ref<SkillDefinition[]>([])
const showAddModal = ref(false)
const selectedSkillId = ref(0)
const proficiencyLevel = ref(3)

onMounted(async () => {
  await operatorStore.fetchSkills()
  await loadAvailableSkills()
})

async function loadAvailableSkills() {
  try {
    const response = await api.get('/skills')
    availableSkills.value = response.data.skills
  } catch (err) {
    console.error('Failed to load skills', err)
  }
}

async function addSkill() {
  if (!selectedSkillId.value) return
  try {
    await operatorStore.addSkill(selectedSkillId.value, proficiencyLevel.value)
    showAddModal.value = false
    selectedSkillId.value = 0
    proficiencyLevel.value = 3
  } catch (err) {
    // Error already handled in store
  }
}

async function removeSkill(id: number) {
  if (!confirm('Remove this skill?')) return
  await operatorStore.removeSkill(id)
}

function getProficiencyLabel(level: number): string {
  const labels = ['', 'Beginner', 'Basic', 'Intermediate', 'Advanced', 'Expert']
  return labels[level] || ''
}
</script>

<template>
  <div class="skills-view">
    <div class="page-header">
      <h1>My Skills</h1>
      <button class="btn btn-primary" @click="showAddModal = true">
        Add Skill
      </button>
    </div>

    <div v-if="operatorStore.loading" class="loading">Loading...</div>

    <div v-else-if="operatorStore.skills.length === 0" class="no-data">
      <p>You haven't added any skills yet</p>
      <p>Add skills to get matched with relevant jobs</p>
      <button class="btn btn-primary" @click="showAddModal = true">
        Add Your First Skill
      </button>
    </div>

    <div v-else class="skills-grid">
      <div v-for="skill in operatorStore.skills" :key="skill.id" class="skill-card">
        <div class="skill-header">
          <h3>{{ skill.skillName }}</h3>
          <button class="remove-btn" @click="removeSkill(skill.id)">×</button>
        </div>
        <span class="category">{{ skill.category }}</span>
        <div class="proficiency">
          <div class="proficiency-bar">
            <div
              class="proficiency-fill"
              :style="{ width: (skill.proficiencyLevel * 20) + '%' }"
            ></div>
          </div>
          <span class="proficiency-label">
            {{ getProficiencyLabel(skill.proficiencyLevel) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Add Skill Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal">
        <h2>Add Skill</h2>
        <form @submit.prevent="addSkill">
          <div class="form-group">
            <label>Skill</label>
            <select v-model="selectedSkillId" required>
              <option :value="0">Select a skill</option>
              <option
                v-for="skill in availableSkills"
                :key="skill.id"
                :value="skill.id"
                :disabled="operatorStore.skills.some(s => s.skillId === skill.id)"
              >
                {{ skill.name }} ({{ skill.category }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Proficiency Level: {{ getProficiencyLabel(proficiencyLevel) }}</label>
            <input
              v-model.number="proficiencyLevel"
              type="range"
              min="1"
              max="5"
              step="1"
            />
            <div class="range-labels">
              <span>Beginner</span>
              <span>Expert</span>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn" @click="showAddModal = false">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Skill</button>
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

.skills-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.skill-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.skill-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.skill-header h3 {
  margin: 0;
}

.remove-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #999;
  cursor: pointer;
  line-height: 1;
}

.remove-btn:hover {
  color: #f44336;
}

.category {
  display: inline-block;
  background: #e3f2fd;
  color: #1976d2;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.85rem;
  margin-bottom: 15px;
}

.proficiency {
  margin-top: 10px;
}

.proficiency-bar {
  height: 8px;
  background: #eee;
  border-radius: 4px;
  overflow: hidden;
}

.proficiency-fill {
  height: 100%;
  background: linear-gradient(90deg, #4caf50, #8bc34a);
  transition: width 0.3s;
}

.proficiency-label {
  display: block;
  text-align: right;
  font-size: 0.85rem;
  color: #666;
  margin-top: 5px;
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
  margin-bottom: 10px;
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
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
}

.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-group input[type="range"] {
  width: 100%;
}

.range-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #999;
}

.modal-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.loading {
  text-align: center;
  padding: 40px;
}
</style>
