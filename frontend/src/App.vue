<script setup>
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell">
    <header v-if="auth.isAuthenticated" class="topbar">
      <span class="brand">Отзывы Яндекс.Карт</span>
      <button class="link-btn" @click="handleLogout">Выйти</button>
    </header>
    <main>
      <router-view />
    </main>
  </div>
</template>
