<script setup>
defineProps({
  rating: { type: [Number, String], default: null },
  ratingsCount: { type: Number, default: null },
  reviewsCount: { type: Number, default: null },
})
</script>

<template>
  <div class="summary-row">
    <div class="summary-item">
      <div class="value">{{ rating ?? '—' }} ★</div>
      <div class="label">Средний рейтинг</div>
    </div>
    <div class="summary-item">
      <div class="value">{{ ratingsCount ?? '—' }}</div>
      <div class="label">Оценок</div>
    </div>
    <div class="summary-item">
      <div class="value">{{ reviewsCount ?? '—' }}</div>
      <div class="label">Отзывов</div>
    </div>
  </div>
</template>
