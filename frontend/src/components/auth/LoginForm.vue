<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')

async function handleSubmit() {
  error.value = ''
  try {
    await authStore.login({ email: email.value, password: password.value })
    const redirect = (route.query.redirect as string) ||
      (authStore.isOperator ? '/operator/dashboard' : '/client/dashboard')
    router.push(redirect)
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Invalid credentials'
  }
}
</script>

<template>
  <form class="login-form" @submit.prevent="handleSubmit">
    <h2>Login</h2>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input
        id="email"
        v-model="email"
        type="email"
        required
        placeholder="Enter your email"
      />
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input
        id="password"
        v-model="password"
        type="password"
        required
        placeholder="Enter your password"
      />
    </div>

    <button type="submit" class="submit-btn" :disabled="authStore.loading">
      {{ authStore.loading ? 'Logging in...' : 'Login' }}
    </button>

    <p class="register-link">
      Don't have an account? <router-link to="/register">Register</router-link>
    </p>
  </form>
</template>

<style scoped>
.login-form {
  max-width: 400px;
  margin: 40px auto;
  padding: 30px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h2 {
  text-align: center;
  margin-bottom: 20px;
  color: #333;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
  color: #666;
}

input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

input:focus {
  outline: none;
  border-color: #2196f3;
}

.submit-btn {
  width: 100%;
  padding: 12px;
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  margin-top: 10px;
}

.submit-btn:hover {
  background: #1976d2;
}

.submit-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.register-link {
  text-align: center;
  margin-top: 20px;
  color: #666;
}

.register-link a {
  color: #2196f3;
}
</style>
