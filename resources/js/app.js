import './bootstrap';
import './shared/debounce';
import './shared/form-submit';
import './shared/searchable-select';
import './shared/searchable-multi-select';
import './shared/currency';
import './shared/print';
import './shared/document-preview';
import './shared/print-signer';
import './shared/nota-sign';
import './shared/delete-form';
import './shared/quick-add-payment-account';

/**
 * Inisialisasi search input (selector: `data-search-debounce`).
 * Pencarian hanya dijalankan saat pengguna menekan Enter (tidak lagi
 * otomatis saat mengetik), sesuai permintaan klien.
 */
document.addEventListener('DOMContentLoaded', function () {
    var searchInputs = document.querySelectorAll('[data-search-debounce]');

    searchInputs.forEach(function (input) {
        var form = input.closest('form');
        if (!form) return;

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });
    });
});
