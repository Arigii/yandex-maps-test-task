<script setup>
import { onMounted, ref, watch } from 'vue'
import { useOrganizationStore } from '../stores/organization'
import http from '../api/http'
import OrganizationSettings from '../components/OrganizationSettings.vue'
import RatingSummary from '../components/RatingSummary.vue'
import ReviewCard from '../components/ReviewCard.vue'
import Pagination from '../components/Pagination.vue'

const store = useOrganizationStore()

const reviews = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const summary = ref({ rating: null, ratings_count: null, reviews_count: null })
const loadingReviews = ref(false)
const reviewsError = ref(null)

async function loadReviews(page = 1) {
  if (!store.organization || store.organization.status !== 'done') return
  loadingReviews.value = true
  reviewsError.value = null
  try {
    const { data } = await http.get(`/organizations/${store.organization.id}/reviews`, { params: { page } })
    reviews.value = data.data
    meta.value = data.meta
    summary.value = data.summary
  } catch (e) {
    reviewsError.value = e.friendlyMessage
  } finally {
    loadingReviews.value = false
  }
}

onMounted(async () => {
  await store.fetchCurrent()
  if (store.isBusy) store.startPolling()
  await loadReviews()
})

watch(
  () => store.organization?.status,
  (status, prevStatus) => {
    if (status === 'done' && prevStatus !== 'done') loadReviews(1)
  }
)
</script>

<template>
  <div>
    <OrganizationSettings />

    <div class="card" v-if="store.organization?.status === 'done'">
      <RatingSummary
        :rating="summary.rating"
        :ratings-count="summary.ratings_count"
        :reviews-count="summary.reviews_count"
      />
    </div>

    <div class="card" v-if="store.organization?.status === 'done'">
      <h3 style="margin-top: 0;">Отзывы</h3>

      <div v-if="loadingReviews" class="empty-state">Загружаем отзывы…</div>
      <p v-else-if="reviewsError" class="error-text">{{ reviewsError }}</p>
      <div v-else-if="reviews.length === 0" class="empty-state">Отзывов пока нет.</div>
      <template v-else>
        <ReviewCard v-for="r in reviews" :key="r.id" :review="r" />
        <Pagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          @change="loadReviews"
        />
      </template>
    </div>
  </div>
</template>
