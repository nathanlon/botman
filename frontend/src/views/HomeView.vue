<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

if (authStore.isAuthenticated) {
  if (authStore.isOperator) {
    router.replace('/operator/dashboard')
  } else {
    router.replace('/client/dashboard')
  }
}
</script>

<template>
  <div class="home">
    <div class="hero">
      <h1>TeleOps Scheduling Platform</h1>
      <p>Connect skilled operators with robotic systems worldwide</p>

      <div class="cta-buttons">
        <router-link to="/login" class="btn btn-primary">Login</router-link>
        <router-link to="/register" class="btn btn-secondary">Register</router-link>
      </div>
    </div>

    <div class="features">
      <div class="feature">
        <h3>For Operators</h3>
        <p>Find flexible teleoperation jobs, manage your schedule, and grow your skills.</p>
      </div>
      <div class="feature">
        <h3>For Businesses</h3>
        <p>Get 24/7 coverage for your robots with skilled, certified operators.</p>
      </div>
      <div class="feature">
        <h3>Smart Matching</h3>
        <p>Our algorithm matches operators to jobs based on skills, availability, and latency.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home {
  text-align: center;
}

.hero {
  padding: 80px 20px;
  background: linear-gradient(135deg, #2196f3, #1976d2);
  color: white;
  border-radius: 8px;
  margin-bottom: 40px;
}

.hero h1 {
  font-size: 2.5rem;
  margin-bottom: 10px;
}

.hero p {
  font-size: 1.2rem;
  opacity: 0.9;
  margin-bottom: 30px;
}

.cta-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.btn {
  padding: 12px 30px;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 500;
}

.btn-primary {
  background: white;
  color: #2196f3;
}

.btn-secondary {
  background: transparent;
  color: white;
  border: 2px solid white;
}

.features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
  padding: 20px;
}

.feature {
  background: white;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.feature h3 {
  color: #2196f3;
  margin-bottom: 10px;
}

.feature p {
  color: #666;
}
</style>
