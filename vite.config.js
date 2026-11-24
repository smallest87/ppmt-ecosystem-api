import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/my-ppmt/config/map-layout.js',
                'resources/js/my-ppmt/engine/iso-math.js',
                'resources/js/my-ppmt/renderers/building-renderer.js',
                'resources/js/my-ppmt/renderers/environment-renderer.js',
                'resources/js/my-ppmt/services/my-ppmt-api.js',
                'resources/js/my-ppmt/ui/hud-controller.js',
                'resources/js/my-ppmt/ui/facade-subscene.js',
                'resources/js/my-ppmt/main.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});