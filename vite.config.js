import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin';

const env = loadEnv('', process.cwd(), '');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
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
