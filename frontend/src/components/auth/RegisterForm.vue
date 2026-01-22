<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const userType = ref<'operator' | 'client'>('operator')
const email = ref('')
const password = ref('')
const fullName = ref('')
const countryCode = ref('US')
const timezoneId = ref('America/New_York')
const companyName = ref('')
const error = ref('')
const success = ref('')

const timezones = [
  'America/New_York',
  'America/Chicago',
  'America/Denver',
  'America/Los_Angeles',
  'Europe/London',
  'Europe/Paris',
  'Europe/Berlin',
  'Asia/Tokyo',
  'Asia/Shanghai',
  'Asia/Singapore',
  'Australia/Sydney',
]

const countries = [
  { code: 'US', name: 'United States' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'DE', name: 'Germany' },
  { code: 'FR', name: 'France' },
  { code: 'JP', name: 'Japan' },
  { code: 'CN', name: 'China' },
  { code: 'SG', name: 'Singapore' },
  { code: 'AU', name: 'Australia' },
]

async function handleSubmit() {
  error.value = ''
  success.value = ''

  try {
    if (userType.value === 'operator') {
      await authStore.registerOperator({
        email: email.value,
        password: password.value,
        fullName: fullName.value,
        countryCode: countryCode.value,
        timezoneId: timezoneId.value,
      })
      success.value = 'Registration successful! Please check your email to verify your account.'
    } else {
      await authStore.registerClient({
        email: email.value,
        password: password.value,
        fullName: fullName.value,
        companyName: companyName.value,
        countryCode: countryCode.value,
      })
      success.value = 'Registration successful! You can now log in.'
    }

    setTimeout(() => router.push('/login'), 2000)
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Registration failed'
  }
}
</script>

<template>
  <form class="register-form" @submit.prevent="handleSubmit">
    <h2>Register</h2>

    <div v-if="error" class="error-message">{{ error }}</div>
    <div v-if="success" class="success-message">{{ success }}</div>

    <div class="user-type-toggle">
      <button
        type="button"
        :class="{ active: userType === 'operator' }"
        @click="userType = 'operator'"
      >
        Operator
      </button>
      <button
        type="button"
        :class="{ active: userType === 'client' }"
        @click="userType = 'client'"
      >
        Client
      </button>
    </div>

    <div class="form-group">
      <label for="fullName">Full Name</label>
      <input id="fullName" v-model="fullName" type="text" required />
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" v-model="email" type="email" required />
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" v-model="password" type="password" required minlength="8" />
      <small>Minimum 8 characters with letters and numbers</small>
    </div>

    <div v-if="userType === 'client'" class="form-group">
      <label for="companyName">Company Name</label>
      <input id="companyName" v-model="companyName" type="text" required />
    </div>

    <div class="form-group">
      <label for="country">Country</label>
      <select id="country" v-model="countryCode" required>
        <option v-for="c in countries" :key="c.code" :value="c.code">
          {{ c.name }}
        </option>
      </select>
    </div>

    <div v-if="userType === 'operator'" class="form-group">
      <label for="timezone">Timezone</label>
      <select id="timezone" v-model="timezoneId" required>
        <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
      </select>
    </div>

    <button type="submit" class="submit-btn" :disabled="authStore.loading">
      {{ authStore.loading ? 'Registering...' : 'Register' }}
    </button>

    <p class="login-link">
      Already have an account? <router-link to="/login">Login</router-link>
    </p>
  </form>
</template>

<style scoped>
.register-form {
  max-width: 450px;
  margin: 40px auto;
  padding: 30px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h2 {
  text-align: center;
  margin-bottom: 20px;
}

.user-type-toggle {
  display: flex;
  margin-bottom: 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.user-type-toggle button {
  flex: 1;
  padding: 10px;
  border: none;
  background: #f5f5f5;
  cursor: pointer;
}

.user-type-toggle button.active {
  background: #2196f3;
  color: white;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
  color: #666;
}

input, select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

small {
  color: #999;
  font-size: 12px;
}

.submit-btn {
  width: 100%;
  padding: 12px;
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px;
}

.submit-btn:disabled {
  background: #ccc;
}

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.success-message {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.login-link {
  text-align: center;
  margin-top: 20px;
}
</style>
