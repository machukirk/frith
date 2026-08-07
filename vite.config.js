import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// No Tailwind and no Bunny font fetch on purpose. The brand hands over a CSS
// custom-property token system as the source of truth (guidelines §10), and a
// second utility system alongside it would just compete with it. Poppins is
// self-hosted from public/fonts, so the page makes no third-party requests.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/frith.css'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
