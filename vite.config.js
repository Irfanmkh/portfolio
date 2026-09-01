import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        host: "0.0.0.0", // Wajib: agar Vite bisa diakses dari luar container
        port: 5173,
        strictPort: true,
        hmr: {
            host: "localhost", // Browser host mengarah ke localhost
        },
        watch: {
            usePolling: true, // Wajib di Docker/WSL agar hot-reload mendeteksi perubahan file
        },
    },
});
