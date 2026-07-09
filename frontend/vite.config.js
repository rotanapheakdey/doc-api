import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [vue()],

    server: {
      host: '0.0.0.0',
      port: 5173,
      proxy: {
        '/api': {
          target: env.VITE_API_PROXY_TARGET || 'http://localhost',
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/api/, '/api')
        }
      },
      watch: {
        usePolling: true,
        interval: 1000,
      }
    },

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    }
  },

  build: {
    outDir: '../public/spa',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router'],
        },
      },
    },
  },

  optimizeDeps: {
    include: ['vue', 'vue-router']
  }
}
})
