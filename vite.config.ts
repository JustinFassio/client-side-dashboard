import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@dashboard': resolve(__dirname, './dashboard'),
      '@features': resolve(__dirname, './features'),
      '@assets': resolve(__dirname, './assets/src'),
    },
  },
  build: {
    outDir: 'assets/dist',
    manifest: true,
    rollupOptions: {
      input: resolve(__dirname, 'assets/src/main.tsx'),
      output: {
        entryFileNames: `js/[name].[hash].js`,
        chunkFileNames: `js/[name].[hash].js`,
        assetFileNames: `[ext]/[name].[hash].[ext]`,
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://localhost:5173',
    hmr: {
      host: 'localhost',
      port: 5173,
      protocol: 'ws',
    },
  },
  optimizeDeps: {
    include: ['react', 'react-dom'],
  },
  // Add more detailed logging
  logLevel: 'info',
}); 