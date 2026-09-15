<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('demo@example.com')
const password = ref('')
const loading = ref(false)
const error = ref(null)

async function onSubmit() {
  loading.value = true
  error.value = null
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.friendlyMessage || 'Не удалось войти.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div style="max-width: 380px; margin: 80px auto;">
    <div class="card">
      <h2 style="margin-top:0;">Вход</h2>
      <p style="color: var(--muted); font-size: 13px;">
        Регистрация не требуется — используйте сид-аккаунт (см. README):
        <br /><code>demo@example.com / demo12345</code>
      </p>
      <form @submit.prevent="onSubmit">
        <div class="field">
          <label>Email</label>
          <input type="email" v-model="email" required autocomplete="username" />
        </div>
        <div class="field">
          <label>Пароль</label>
          <input type="password" v-model="password" required autocomplete="current-password" />
        </div>
        <p v-if="error" class="error-text">{{ error }}</p>
        <button class="primary" type="submit" :disabled="loading">
          {{ loading ? 'Входим…' : 'Войти' }}
        </button>
      </form>
    </div>
  </div>
</template>
