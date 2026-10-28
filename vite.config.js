import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Two families only: Big Shoulders (formerly "Big Shoulders Display"), a condensed signage face
// that reads like the fleet's cab lettering, for headings and figures; Overpass, drawn from the
// Highway Gothic road-sign alphabet, for everything else.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Big Shoulders', {
                    alias: 'display',
                    variable: '--ff-display',
                    weights: [700, 800],
                    styles: ['normal'],
                    subsets: ['latin'],
                    preload: [{ weight: 800 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('Overpass', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 600, 700],
                    styles: ['normal'],
                    subsets: ['latin'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
