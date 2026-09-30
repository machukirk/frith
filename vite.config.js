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

                // The registration wizard has not been rebuilt against the
                // hi-fi yet and is live taking registrations, so its original
                // stylesheet ships alongside the new one until it has. It goes
                // when resources/views/register is redrawn.
                'resources/css/frith.css',
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
