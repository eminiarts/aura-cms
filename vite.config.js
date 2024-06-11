import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ command, mode }) => {
    if (command === 'build' && mode === 'lib') {
        return {
            build: {
                sourcemap: true,
                emptyOutDir: true,
                copyPublicDir: true,
            },
            plugins: [
                laravel({
                    input: [
                        'resources/js/monaco.js',
                        'resources/js/apexcharts.js'
                    ],
                    publicDirectory: 'resources',
                    buildDirectory: 'dist/libs',
                })
            ],
        }
    }

    return {
        build: {
            sourcemap: true,
            emptyOutDir: true,
            copyPublicDir: true,
        },
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                ],
                refresh: true,
                publicDirectory: 'resources',
                buildDirectory: 'dist',
            })
        ],
    }
});
