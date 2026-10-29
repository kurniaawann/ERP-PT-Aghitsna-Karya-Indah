/**
 * Rekap Penjualan — JavaScript Halaman Index
 *
 * Fitur:
 * - Checkbox "Pilih Semua" & status tombol hapus massal (#delete-button)
 * - Submit form hapus massal dengan indikator loading
 * - Filter auto-submit (bulan / tahun / status)
 *
 * Form tambah/edit rekap kini berupa halaman tersendiri
 * (resources/js/pages/finance/sales-recaps/form.js).
 */

/* global resetFormSubmitState */

// ============================================================
// HAPUS MASSAL
// ============================================================

/**
 * Submit form hapus massal.
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    const form = document.getElementById('deleteForm');
    if (form) form.submit();
}
window.submitDeleteForm = submitDeleteForm;

// ============================================================
// INISIALISASI
// ============================================================

/**
 * Inisialisasi halaman daftar rekap penjualan.
 *
 * Alur:
 * - Checkbox select all untuk hapus massal.
 * - Filter auto-submit bulan/tahun/status.
 * - Reset status submit saat navigasi kembali (pageshow).
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // CHECKBOX SELECT ALL
    // ============================================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const saleCheckboxes = document.querySelectorAll('input[name="selected_sales[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update status tombol hapus massal berdasarkan checkbox tercentang.
     *
     * Tombol #delete-button dinonaktifkan bila tidak ada rekap dipilih.
     */
    function updateDeleteButtonState() {
        const anyChecked = Array.from(saleCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            saleCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    saleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = Array.from(saleCheckboxes).every(cb => cb.checked);
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ============================================================
    // FILTER AUTO-SUBMIT
    // ============================================================

    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');
    const statusSelect = document.getElementById('status-select');
    const filterForm = monthSelect ? monthSelect.closest('form') : null;

    if (monthSelect && filterForm) {
        monthSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (yearSelect && filterForm) {
        yearSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (statusSelect && filterForm) {
        statusSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    // ============================================================
    // RESET STATUS SUBMIT SAAT HALAMAN DITAMPILKAN
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
