<script setup>
defineProps({ status: { type: String, required: true } })

const labels = {
  pending: 'Не подключено',
  queued: 'В очереди',
  parsing: 'Парсим…',
  done: 'Готово',
  failed: 'Ошибка',
  structure_changed: 'Изменилась разметка источника',
}
</script>

<template>
  <span class="badge" :class="status">{{ labels[status] || status }}</span>
</template>
