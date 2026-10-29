/**
 * Faktur Pembelian - JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Format harga jual & hitung PPN + total di modal Edit
 * - Loading state submit modal Edit (anti double-submit)
 * - Checkbox "Pilih Semua" & status tombol hapus massal (#delete-button)
 * - Submit form hapus massal
 * - Penyimpanan gagal → modal Edit asal dibuka lagi
 *
 * Tambah faktur kini berupa halaman tersendiri
 * (resources/js/pages/finance/purchase-invoices/create.js).
 */

/* global parseCurrencyInput, handleFormSubmit, resetFormSubmitState */

// ==========================================
// HELPER
// ==========================================

/**
 * Parse input desimal (mendukung koma sebagai pemisah desimal).
 *
 * @param  {string|number} value
 * @return {number}
 */
function parseDecimalInput(value) {
    const rawValue = String(value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

/**
 * Format angka sebagai ribuan Indonesia tanpa "Rp" (prefix "Rp" sudah ada
 * di field).
 *
 * @param  {number} value
 * @return {string}
 */
function formatNumber(value) {
    return (Number(value) || 0).toLocaleString('id-ID');
}

// ==========================================
// PPN CALCULATION (MODAL EDIT)
// ==========================================

/**
 * Hitung PPN & total pada satu modal edit.
 *
 * @param  {string} invoiceId
 * @return {void}
 */
function calculateEditPpn(invoiceId) {
    const sellingPriceInput = document.getElementById('editSellingPrice-' + invoiceId);
    const ppnPercentageInput = document.getElementById('editPpnPercentage-' + invoiceId);
    const ppnTaxInput = document.getElementById('editPpnTax-' + invoiceId);
    const totalInput = document.getElementById('editTotal-' + invoiceId);
    if (!sellingPriceInput || !ppnPercentageInput || !ppnTaxInput) return;

    const sellingPrice = parseCurrencyInput(sellingPriceInput.value) || 0;
    const ppnTax = Math.round((sellingPrice * parseDecimalInput(ppnPercentageInput.value)) / 100);

    ppnTaxInput.value = formatNumber(ppnTax);
    if (totalInput) totalInput.value = formatNumber(sellingPrice + ppnTax);
}

/**
 * Pasang kalkulasi PPN pada semua modal edit (#editSellingPrice-{id}).
 *
 * @return {void}
 */
function initEditModalsPpnCalculation() {
    document.querySelectorAll('[id^="editSellingPrice-"]').forEach(function (sellingPriceInput) {
        const invoiceId = sellingPriceInput.id.replace('editSellingPrice-', '');
        const ppnPercentageInput = document.getElementById('editPpnPercentage-' + invoiceId);
        if (!ppnPercentageInput) return;

        sellingPriceInput.addEventListener('input', function () {
            const digits = sellingPriceInput.value.replace(/[^\d]/g, '');
            sellingPriceInput.value = digits === '' ? '' : formatNumber(parseInt(digits, 10));
            calculateEditPpn(invoiceId);
        });

        ppnPercentageInput.addEventListener('input', function () {
            calculateEditPpn(invoiceId);
        });

        calculateEditPpn(invoiceId);
    });
}

// ==========================================
// BULK DELETE
// ==========================================

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
    if (form) form.submit();
};

/**
 * Checkbox "Pilih Semua" + status tombol hapus massal.
 *
 * @return {void}
 */
function initSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(invoiceCheckboxes).some(function (cb) { return cb.checked; });
        if (deleteButton) deleteButton.disabled = !anyChecked;
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

// ==========================================
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    initEditModalsPpnCalculation();
    initSelectAllCheckbox();

    // Loading state submit modal edit
    document.querySelectorAll('[id^="editModal-"]').forEach(function (editModal) {
        const editForm = editModal.querySelector('form');
        const editButton = editModal.querySelector('form button[type="submit"]');
        if (!editForm || !editButton) return;

        editForm.addEventListener('submit', function (e) {
            if (!handleFormSubmit(editButton, null, 'Menyimpan...')) e.preventDefault();
        });
    });

    // Penyimpanan gagal → buka lagi modal edit asal (berisi input lama + pesan error)
    const reopenModalId = window.PURCHASE_INVOICE_DATA?.reopenModal;
    if (reopenModalId && document.getElementById(reopenModalId) && typeof openModal === 'function') {
        openModal(reopenModalId);
    }

    // Reset status submit saat halaman ditampilkan lagi (navigasi kembali/maju)
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
