import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
    ],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                // Default theme
                docs: 'resources/js/docs.js',
                'docs-css': 'resources/css/docs.css',
                // Modern theme
                'themes/modern': 'resources/js/themes/modern.js',
                'themes/modern-css': 'resources/css/themes/modern.css',
                // Aurora theme
                'themes/aurora': 'resources/js/themes/aurora.js',
                'themes/aurora-css': 'resources/css/themes/aurora.css',
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
});
