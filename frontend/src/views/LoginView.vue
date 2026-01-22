<script setup lang="ts">
import LoginForm from '@/components/auth/LoginForm.vue'
</script>

<template>
  <div class="login-view">
    <LoginForm />
  </div>
</template>

<style scoped>
.login-view {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
