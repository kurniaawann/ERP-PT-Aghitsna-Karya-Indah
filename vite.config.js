import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    //  server: {
    //    host: '0.0.0.0',
    //    port: 5173,
    //   hmr: {
    //        host: 'localhost',
    //    },
    // },
    //untuk keperluan ngrok
     server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/inventory/items/index.js', 'resources/js/pages/inventory/incoming-items/index.js', 'resources/js/pages/inventory/outgoing-items/index.js', 'resources/js/pages/inventory/item-returns/index.js', 'resources/js/pages/inventory/stock-report.js', 'resources/js/pages/finance/item-invoices/index.js', 'resources/js/pages/finance/aluminium-invoices/index.js', 'resources/js/pages/finance/sales-recaps/index.js', 'resources/js/pages/finance/aluminium-recaps/index.js', 'resources/js/pages/finance/expense-recaps/index.js', 'resources/js/pages/finance/reimburse/index.js', 'resources/js/pages/finance/payment-proofs/index.js', 'resources/js/pages/finance/project-invoices/index.js', 'resources/js/pages/finance/purchase-invoices/index.js', 'resources/js/pages/finance/payment-accounts/index.js', 'resources/js/pages/report/transaction-categories/index.js', 'resources/js/pages/report/sales-reports/index.js', 'resources/js/pages/report/expense-reports/index.js', 'resources/js/pages/sdm/employee/index.js', 'resources/js/pages/sdm/kasbon/index.js', 'resources/js/pages/sdm/overtime/index.js', 'resources/js/pages/sdm/attendance/index.js', 'resources/js/pages/sdm/division/index.js', 'resources/js/pages/sdm/executive/index.js', 'resources/js/pages/sdm/payroll/index.js', 'resources/js/pages/administrasi/nota/index.js', 'resources/js/pages/administrasi/document-receipt/index.js', 'resources/js/pages/administrasi/kwitansi/index.js', 'resources/js/pages/administrasi/delivery-notes/index.js', 'resources/js/pages/administrasi/aluminium-quotation/index.js', 'resources/js/pages/administrasi/project-quotation/index.js', 'resources/js/pages/administrasi/cash-out-proof/index.js', 'resources/js/pages/administrasi/rab/index.js', 'resources/js/pages/user-management/index.js', 'resources/js/pages/notification/salary-reminder/index.js', 'resources/js/pages/notification/invoice-proyek-reminder/index.js'],
            refresh: true,
        }),
    ],
});
