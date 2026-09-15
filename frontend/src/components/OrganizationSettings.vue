<script setup>
import { ref, watch } from 'vue'
import { useOrganizationStore } from '../stores/organization'
import StatusBadge from './StatusBadge.vue'

const store = useOrganizationStore()
const url = ref('')
const submitting = ref(false)
const localError = ref(null)

watch(
  () => store.organization,
  (org) => { if (org?.source_url && !url.value) url.value = org.source_url },
  { immediate: true }
)

async function onSubmit() {
  submitting.value = true
  localError.value = null
  try {
    await store.submitUrl(url.value)
  } catch (e) {
    localError.value = e.friendlyMessage
  } finally {
    submitting.value = false
  }
}

async function onReparse() {
  await store.reparse()
}
</script>

<template>
  <div class="card">
    <h3 style="margin-top: 0;">Карточка организации на Яндекс.Картах</h3>
    <form @submit.prevent="onSubmit">
      <div class="field">
        <label>Ссылка на организацию</label>
        <input
          type="text"
          v-model="url"
          placeholder="https://yandex.ru/maps/org/название/1234567890/"
          required
        />
      </div>
      <p v-if="localError" class="error-text">{{ localError }}</p>
      <button class="primary" type="submit" :disabled="submitting || store.isBusy">
        {{ submitting ? 'Сохраняем…' : 'Сохранить и получить отзывы' }}
      </button>
    </form>

    <div v-if="store.organization" style="margin-top: 18px;">
      <div style="display:flex; align-items:center; gap:10px;">
        <StatusBadge :status="store.organization.status" />
        <button
          v-if="!store.isBusy"
          class="link-btn"
          style="text-decoration: underline;"
          @click="onReparse"
        >
          Перепарсить заново
        </button>
      </div>

      <div v-if="store.isBusy" class="progress-bar">
        <div :style="{ width: (store.organization.progress || 0) + '%' }"></div>
      </div>

      <p v-if="store.organization.status === 'structure_changed'" class="error-text">
        Яндекс изменил разметку/формат ответа — парсер это обнаружил и остановился,
        чтобы не записать в БД мусор. Подробности в логах бэкенда.
      </p>
      <p v-else-if="store.organization.last_error" class="error-text">
        {{ store.organization.last_error }}
      </p>
    </div>
  </div>
</template>
