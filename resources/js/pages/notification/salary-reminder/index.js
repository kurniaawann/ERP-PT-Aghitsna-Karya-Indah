/**
 * Reminder Gaji Karyawan - JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Auto-submit form pada perubahan filter (bulan, tahun, status)
 * - Debounce pada input pencarian karyawan
 * - Inisialisasi behavior halaman laporan reminder gaji
 */

/**
 * Inisialisasi halaman Reminder Gaji Karyawan.
 * Dipanggil setelah DOM selesai dimuat.
 *
 * Alur:
 * 1. Cari form filter (#filterForm); bila tidak ada, hentikan inisialisasi.
 * 2. Auto-submit dropdown: event 'change' pada tiap <select> (bulan/tahun/status)
 *    langsung mem-submit form → data reminder ter-refresh sesuai filter
 *    (backend: SalaryReminderService::getPaginatedReminders).
 * 3. Debounce pencarian: input name="search" mem-submit form 500ms setelah user
 *    berhenti mengetik sehingga tidak ada request per karakter.
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', function () {

    var filterForm = document.getElementById('filterForm');
    if (!filterForm) return;

    // ─── Kirim otomatis pada filter dropdown ───────────────────────────
    /**
     * Auto-submit form saat nilai dropdown filter berubah.
     *
     * Alur: event 'change' pada setiap <select> dalam #filterForm memanggil
     * filterForm.submit(), sehingga daftar reminder di-reload dengan filter
     * bulan/tahun/status terbaru tanpa tombol submit manual.
     *
     * @param {HTMLSelectElement} select  Elemen dropdown filter yang berubah
     * @returns {void}
     */
    var filterSelects = filterForm.querySelectorAll('select');
    filterSelects.forEach(function (select) {
        select.addEventListener('change', function () {
            filterForm.submit();
        });
    });

    // ─── Debounce pada input pencarian ─────────────────────────────────
    /**
     * Debounce pencarian karyawan (500ms).
     *
     * Alur: setiap keystroke pada input name="search" membatalkan timer
     * sebelumnya (clearTimeout) lalu membuat timer baru 500ms; jika user
     * berhenti mengetik dalam rentang itu, form di-submit sehingga pencarian
     * hanya memicu satu request setelah jeda.
     *
     * @returns {void}
     */
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
