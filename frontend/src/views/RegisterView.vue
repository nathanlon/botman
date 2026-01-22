<script setup lang="ts">
import RegisterForm from '@/components/auth/RegisterForm.vue'
</script>

<template>
  <div class="register-view">
    <RegisterForm />
  </div>
</template>

<style scoped>
.register-view {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
