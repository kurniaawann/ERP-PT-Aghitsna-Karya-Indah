/**
 * Reminder Gaji Karyawan - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Auto-submit form pada perubahan filter (bulan, tahun, status)
 * - Debounce pada input pencarian karyawan
 * - Inisialisasi behavior halaman laporan reminder gaji
 */

/**
 * Inisialisasi halaman Reminder Gaji Karyawan.
 * Dipanggil setelah DOM selesai dimuat.
 */
document.addEventListener('DOMContentLoaded', function () {

    var filterForm = document.getElementById('filterForm');
    if (!filterForm) return;

    // ─── Auto-submit pada filter dropdown ──────────────────────────────
    var filterSelects = filterForm.querySelectorAll('select');
    filterSelects.forEach(function (select) {
        select.addEventListener('change', function () {
            filterForm.submit();
        });
    });

    // ─── Debounce pada input pencarian ─────────────────────────────────
    var searchInput = filterForm.querySelector('input[name="search"]');
    var searchTimeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var form = this.closest('form');
            if (!form) return;

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                form.submit();
            }, 500);
        });
    }
});
