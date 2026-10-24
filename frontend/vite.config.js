import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// El proxy envía todo lo que empiece con /api hacia Laravel (php artisan serve)
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    proxy: {
      '/api': { target: 'http://127.0.0.1:8000', changeOrigin: true },
    },
  },
})
