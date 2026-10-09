import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    // loadEnv: vite tidak otomatis menaruh isi .env Laravel ke process.env saat
    // membaca file config ini, jadi VITE_DEV_ORIGIN harus ditarik manual.
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: true,
            // VITE_DEV_ORIGIN: dipakai saat dev server diakses dari perangkat lain di
            // jaringan (mis. HP) — tanpa ini, public/hot menulis host bind literal
            // (mis. 0.0.0.0) yang tak bisa dituju browser lain. Opsional, default kosong.
            // `cors: true` wajib didampingkan — tanpanya Vite mengirim header
            // Access-Control-Allow-Origin tetap = nilai `origin`, menolak origin
            // Laravel yang sesungguhnya (beda port = beda origin bagi browser).
            ...(env.VITE_DEV_ORIGIN ? { origin: env.VITE_DEV_ORIGIN, cors: true } : {}),
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
