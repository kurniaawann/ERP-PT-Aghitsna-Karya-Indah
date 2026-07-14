import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/inventory/items/index.js', 'resources/js/pages/inventory/incoming-items/index.js', 'resources/js/pages/inventory/outgoing-items/index.js', 'resources/js/pages/inventory/item-returns/index.js', 'resources/js/pages/inventory/stock-reports/index.js', 'resources/js/pages/finance/product-invoices/index.js', 'resources/js/pages/finance/aluminium-invoices/index.js', 'resources/js/pages/finance/sales-recaps/index.js', 'resources/js/pages/finance/aluminium-recaps/index.js'],
            refresh: true,
        }),
    ],
});
