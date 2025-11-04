import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import Vue from '@vitejs/plugin-vue';
import { createHtmlPlugin } from 'vite-plugin-html'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/sass/style.scss',
                'resources/sass/dashboard.scss',
                'resources/sass/employee.scss',
                'resources/sass/auth.scss',
                'resources/sass/errors.scss',
                'resources/js/app.js',
                'resources/js/auth.js'
            ],
            refresh: true,
        }),
        Vue(),
        vueDevTools(),
        createHtmlPlugin({})
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
            '~': '/resources',
        },
    },
});
