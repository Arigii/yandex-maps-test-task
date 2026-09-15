import axios from 'axios'

const http = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
})

export async function ensureCsrfCookie() {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const message =
      error.response?.data?.message ||
      (error.response?.status === 401 && 'Нужно войти в систему.') ||
      (error.response?.status === 422 && Object.values(error.response?.data?.errors || {}).flat()[0]) ||
      'Что-то пошло не так. Попробуйте ещё раз.'

    return Promise.reject(Object.assign(error, { friendlyMessage: message }))
  }
)

export default http
