import { fileURLToPath, URL } from 'node:url';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    resolve: {
        alias: {
            '@': fileURLToPath(
                new URL('./resources/js', import.meta.url),
            ),
        },
    },

    test: {
        environment: 'jsdom',
        setupFiles: [
            './resources/js/tests/setup.ts',
        ],
        include: [
            'resources/js/**/*.test.ts',
        ],
    },
});
