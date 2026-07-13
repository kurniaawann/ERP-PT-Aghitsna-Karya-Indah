import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/inventory/items/index.js', 'resources/js/pages/inventory/incoming-items/index.js', 'resources/js/pages/inventory/outgoing-items/index.js', 'resources/js/pages/inventory/item-returns/index.js'],
            refresh: true,
        }),
    ],
});
