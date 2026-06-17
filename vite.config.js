import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import Vue from '@vitejs/plugin-vue';

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
                'resources/sass/underconstruction.scss',
                'resources/js/app.js',
                'resources/js/auth.js',
                'resources/js/applicant/recruitment.js'
            ],
            refresh: true,
        }),
        Vue(),
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
            '~': '/resources',
        },
    },
});
