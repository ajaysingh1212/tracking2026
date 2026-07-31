import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/gps-watcher.js',
                'resources/js/live-map.js',
                'resources/js/private-chat.js',
                'resources/js/geofence-manager.js',
                'resources/js/report-page.js',
            ],
            refresh: true,
        }),
    ],
});
