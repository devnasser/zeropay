import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
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
    
    // Performance optimizations
    build: {
        rollupOptions: {
            output: {
                // Manual chunk splitting for better caching
                manualChunks: {
                    'vendor': ['vue', 'axios', 'alpinejs'],
                    'ui': ['@headlessui/vue', '@heroicons/vue'],
                    'utils': ['lodash', 'dayjs'],
                },
                // Content hash for cache busting
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Split chunks at 500kb
        chunkSizeWarningLimit: 500,
        // Enable source maps for production debugging
        sourcemap: false,
        // CSS code splitting
        cssCodeSplit: true,
        // Tree shaking
        treeShake: true,
    },
    
    // Development optimizations
    server: {
        hmr: {
            overlay: false,
        },
        watch: {
            usePolling: false,
        },
    },
    
    // Dependency optimization
    optimizeDeps: {
        include: [
            'vue',
            'axios',
            'alpinejs',
            '@headlessui/vue',
            'lodash',
            'dayjs',
        ],
        exclude: ['@livewire'],
    },
    
    // Enable caching
    cacheDir: 'node_modules/.vite',
    
    // Performance hints
    resolve: {
        alias: {
            '@': '/resources/js',
            '~': '/resources',
        },
    },
});
