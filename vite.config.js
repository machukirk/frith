import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// SASS with BEM, mobile first. No utility framework: the hi-fi is a designed
// system with its own scale, and a second one alongside it would only compete.
// Livvic and Caudex are self-hosted from public/fonts, so the page makes no
// third-party requests and nothing blocks the first paint.
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/main.scss',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
