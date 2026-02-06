import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/',
  build: {
    outDir: '../public',
    emptyOutDir: false,
    sourcemap: false,
    rollupOptions: {
      input: {
        main: './index.html',
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
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
