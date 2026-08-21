import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin';
import { iconGenerationPlugin } from './bin/icons/vite-plugin.js';

const env = loadEnv('', process.cwd(), '');

export default defineConfig({
    plugins: [
        iconGenerationPlugin(),
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/icons/apple-touch-icon.png',
                'resources/icons/favicon-96x96.png',
                'resources/icons/favicon.ico',
                'resources/icons/favicon.svg',
                'resources/icons/web-app-manifest-192x192.png',
                'resources/icons/web-app-manifest-512x512.png',
            ],
            refresh: true,
        }),
    ],
  server: {
    hmr: {
      host: env.APP_HOST,
      port: 5173,
    },
    watch: {
      usePolling: true,
    },
    host: '0.0.0.0',
    port: 5173,
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler' // or "modern"
      }
    }
  }
});
