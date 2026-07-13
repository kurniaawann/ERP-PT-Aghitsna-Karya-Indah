import './bootstrap';
import './shared/form-submit';
import './shared/searchable-select';
import './shared/currency';
import './shared/debounce';

/**
 * Inisialisasi debounced search untuk semua search input.
 * Menggunakan data attribute `data-search-debounce` sebagai selector.
 */
document.addEventListener('DOMContentLoaded', function () {
    var searchInputs = document.querySelectorAll('[data-search-debounce]');

    searchInputs.forEach(function (input) {
        var form = input.closest('form');
        if (!form) return;

        var debouncedSubmit = debounce(function () {
            form.submit();
        }, 500);

        input.addEventListener('input', debouncedSubmit);
    });
});
