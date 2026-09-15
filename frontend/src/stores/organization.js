import { defineStore } from 'pinia'
import http from '../api/http'

export const useOrganizationStore = defineStore('organization', {
  state: () => ({
    organization: null,
    loading: false,
    error: null,
    pollTimer: null,
  }),
  getters: {
    isBusy: (state) => ['queued', 'parsing'].includes(state.organization?.status),
  },
  actions: {
    async fetchCurrent() {
      this.loading = true
      this.error = null
      try {
        const { data } = await http.get('/organizations/current')
        this.organization = data.organization
      } catch (e) {
        this.error = e.friendlyMessage
      } finally {
        this.loading = false
      }
    },

    async submitUrl(url) {
      this.loading = true
      this.error = null
      try {
        const { data } = await http.post('/organizations', { url })
        this.organization = data.organization
        this.startPolling()
      } catch (e) {
        this.error = e.friendlyMessage
        throw e
      } finally {
        this.loading = false
      }
    },

    async reparse() {
      if (!this.organization) return
      await http.post(`/organizations/${this.organization.id}/reparse`)
      this.startPolling()
    },

    startPolling() {
      this.stopPolling()
      this.pollTimer = setInterval(async () => {
        if (!this.organization) return this.stopPolling()
        try {
          const { data } = await http.get(`/organizations/${this.organization.id}/status`)
          this.organization = { ...this.organization, ...data }
          if (!['queued', 'parsing'].includes(data.status)) this.stopPolling()
        } catch {
          this.stopPolling()
        }
      }, 2000)
    },

    stopPolling() {
      if (this.pollTimer) clearInterval(this.pollTimer)
      this.pollTimer = null
    },
  },
})
