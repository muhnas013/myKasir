import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        // VITE_DEV_ORIGIN: dipakai saat dev server diakses dari perangkat lain di
        // jaringan (mis. HP) — tanpa ini, public/hot menulis host bind literal
        // (mis. 0.0.0.0) yang tak bisa dituju browser lain. Opsional, default kosong.
        ...(process.env.VITE_DEV_ORIGIN ? { origin: process.env.VITE_DEV_ORIGIN } : {}),
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
