import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/react/',
  build: {
    outDir: '../public/react',
    emptyOutDir: true,
    sourcemap: false,
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'https://api.siteaccess.ru',
        changeOrigin: true,
        secure: true,
      }
    }
  }
})
