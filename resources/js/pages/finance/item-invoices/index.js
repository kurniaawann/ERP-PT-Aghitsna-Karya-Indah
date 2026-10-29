/**
 * Invoice Barang - JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Checkbox "Pilih Semua" & status tombol hapus massal (#delete-button)
 * - Submit form hapus massal dengan indikator loading
 *
 * Form tambah/edit invoice kini berupa halaman tersendiri
 * (resources/js/pages/finance/item-invoices/form.js).
 */

/* global resetFormSubmitState */

// ─── Submit Form Hapus ───────────────────────────────────────────────────────

/**
 * Submit form hapus massal dengan indikator loading pada tombol konfirmasi.
 */
window.submitDeleteForm = function () {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (form) {
        form.submit();
    }
};

// ─── Pilih Semua ─────────────────────────────────────────────────────────────

/**
 * Inisialisasi checkbox "Pilih Semua" untuk hapus massal.
 *
 * Alur:
 * - Saat #selectAll berubah → semua checkbox invoice mengikuti.
 * - Saat checkbox per baris berubah → status selectAll disinkronkan, dan
 *   tombol hapus #delete-button diaktifkan/dinonaktifkan berdasarkan ada
 *   tidaknya checkbox yang tercentang.
 */
function initSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(invoiceCheckboxes).some(function (cb) { return cb.checked; });
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            invoiceCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    invoiceCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = Array.from(invoiceCheckboxes).every(function (cb) { return cb.checked; });
            }

            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}

// ─── INISIALISASI ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    initSelectAllCheckbox();

    // Reset status submit saat halaman ditampilkan lagi (navigasi kembali/maju)
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
