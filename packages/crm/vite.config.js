import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    resolve: {
        alias: {
            '@crm': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['packages/crm/resources/js/app.js'],
            buildDirectory: 'build/crm',
            refresh: [
                'packages/crm/resources/js/**',
                'packages/crm/resources/css/**',
                'packages/crm/routes/**',
                'packages/crm/src/**',
            ],
        }),
        vue(),
    ],
});
