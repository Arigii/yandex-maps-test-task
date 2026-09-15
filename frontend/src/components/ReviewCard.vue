<script setup>
defineProps({ review: { type: Object, required: true } })

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('ru-RU', { day: '2-digit', month: 'long', year: 'numeric' })
}
</script>

<template>
  <div class="review-card">
    <div class="review-head">
      <span class="review-author">{{ review.author || 'Аноним' }}</span>
      <span class="review-date">{{ formatDate(review.published_at) }}</span>
    </div>
    <div class="review-stars">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</div>
    <p class="review-text">{{ review.text }}</p>
  </div>
</template>
