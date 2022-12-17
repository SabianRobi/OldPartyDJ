import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Styles
                'resources/css/app.css',
                // '/node_modules/flowbite/dist/flowbite.css',

                // Scripts
                'resources/js/app.js',
                // '/node_modules/flowbite/dist/flowbite.js',
            ],
            refresh: true,
        }),
    ],
});
