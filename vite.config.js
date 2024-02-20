import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import MonacoWebpackPlugin from 'monaco-editor-webpack-plugin';

export default defineConfig({
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
                'resources/js/apexcharts.js',
                'resources/js/monaco.js'
            ],
            refresh: true,
            publicDirectory: 'resources',
            buildDirectory: 'dist',
        })
    ],
});
