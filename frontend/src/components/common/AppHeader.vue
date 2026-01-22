<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const navItems = computed(() => {
  if (authStore.isOperator) {
    return [
      { label: 'Dashboard', route: '/operator/dashboard' },
      { label: 'My Shifts', route: '/operator/shifts' },
      { label: 'Marketplace', route: '/operator/marketplace' },
      { label: 'Skills', route: '/operator/skills' },
      { label: 'Availability', route: '/operator/availability' },
    ]
  }
  if (authStore.isClient) {
    return [
      { label: 'Dashboard', route: '/client/dashboard' },
      { label: 'Sites', route: '/client/sites' },
      { label: 'Jobs', route: '/client/jobs' },
    ]
  }
  return []
})

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header class="app-header">
    <div class="header-content">
      <div class="logo">
        <router-link to="/">TeleOps</router-link>
      </div>

      <nav class="nav-links">
        <router-link
          v-for="item in navItems"
          :key="item.route"
          :to="item.route"
          class="nav-link"
        >
          {{ item.label }}
        </router-link>
      </nav>

      <div class="user-menu">
        <span class="user-name">{{ authStore.user?.fullName }}</span>
        <button class="logout-btn" @click="handleLogout">Logout</button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.app-header {
  background: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 0 20px;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 60px;
}

.logo a {
  font-size: 1.5rem;
  font-weight: bold;
  color: #2196f3;
  text-decoration: none;
}

.nav-links {
  display: flex;
  gap: 20px;
}

.nav-link {
  color: #666;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 4px;
  transition: background 0.2s;
}

.nav-link:hover,
.nav-link.router-link-active {
  background: #f0f0f0;
  color: #2196f3;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-name {
  color: #666;
}

.logout-btn {
  padding: 8px 16px;
  background: #f44336;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.logout-btn:hover {
  background: #d32f2f;
}
</style>
