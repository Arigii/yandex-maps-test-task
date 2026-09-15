<script setup>
const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
})
const emit = defineEmits(['change'])

function pagesToShow() {
  const pages = new Set([1, props.lastPage, props.currentPage, props.currentPage - 1, props.currentPage + 1])
  return [...pages].filter((p) => p >= 1 && p <= props.lastPage).sort((a, b) => a - b)
}
</script>

<template>
  <div class="pagination" v-if="lastPage > 1">
    <button :disabled="currentPage === 1" @click="emit('change', currentPage - 1)">‹</button>
    <template v-for="(p, idx) in pagesToShow()" :key="p">
      <span v-if="idx > 0 && p - pagesToShow()[idx - 1] > 1" style="padding: 6px 2px;">…</span>
      <button :class="{ active: p === currentPage }" @click="emit('change', p)">{{ p }}</button>
    </template>
    <button :disabled="currentPage === lastPage" @click="emit('change', currentPage + 1)">›</button>
  </div>
</template>
