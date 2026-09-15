import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// В Docker Compose фронтенд бежит в СВОЁМ контейнере, поэтому "localhost"
// внутри него — это сам контейнер, а не хост-машина и не nginx-контейнер.
// Поэтому цель прокси берём из переменной окружения (задаётся в
// docker-compose.yml как http://nginx), а для локального запуска без
// Docker (npm run dev на хосте) используем localhost:8000 по умолчанию.
const proxyTarget = process.env.VITE_PROXY_TARGET || 'http://localhost:8000'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': { target: proxyTarget, changeOrigin: true },
      '/sanctum': { target: proxyTarget, changeOrigin: true },
    },
  },
})
