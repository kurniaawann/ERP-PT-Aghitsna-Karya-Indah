/**
 * Barang Keluar (Stock Out) — Page Module
 *
 * Halaman ini bersifat read-only (hanya menampilkan data dan export).
 * JS yang dibutuhkan hanya print dropdown functionality.
 */

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // PRINT DROPDOWN
    // ==========================================

    const printDropdownButton = document.getElementById('printDropdownButton');
    const printDropdownMenu = document.getElementById('printDropdownMenu');

    if (printDropdownButton && printDropdownMenu) {
        /**
         * Toggle dropdown menu saat tombol print diklik.
         */
        printDropdownButton.addEventListener('click', function (e) {
            e.stopPropagation();
            printDropdownMenu.classList.toggle('hidden');
        });

        /**
         * Tutup dropdown jika klik di luar area dropdown.
         */
        document.addEventListener('click', function (e) {
            if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                printDropdownMenu.classList.add('hidden');
            }
        });
    }
});
