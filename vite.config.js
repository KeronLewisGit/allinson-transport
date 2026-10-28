import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Matched to the company's own branding: Roboto Condensed for headings, the closest open face to
// the bold condensed grotesque of its "GEORGE ALLINSON (TRANSPORT) LTD" header and trailer
// lettering; body text is set in Trebuchet MS, the face its current website uses, with Fira Sans
// (self-hosted) for devices that do not have Trebuchet (Android, Linux).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Roboto Condensed', {
                    alias: 'display',
                    variable: '--ff-display',
                    weights: [700, 800],
                    styles: ['normal'],
                    subsets: ['latin'],
                    preload: [{ weight: 700 }],
                    fallbacks: ['Arial Narrow', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('Fira Sans', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 700],
                    styles: ['normal'],
                    subsets: ['latin'],
                    preload: false,
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
