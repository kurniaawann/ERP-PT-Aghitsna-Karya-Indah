/**
 * Invoice Proyek — JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Checkbox "Pilih Semua" & status tombol hapus massal (#delete-button)
 * - Submit form hapus massal dengan indikator loading
 * - Hapus invoice tunggal (modal deleteModal-{no} → form#deleteForm-{no})
 * - Parser/format mata uang (window.parseCurrencyInput /
 *   window.formatCurrencyInput) — tetap dipasang seperti sebelumnya karena
 *   dipakai field nominal di modal Upload Bukti Pembayaran.
 *
 * Form tambah/edit invoice kini berupa halaman tersendiri
 * (resources/js/pages/finance/project-invoices/form.js).
 *
 * Referensi backend: app/Services/Finance/ProyekInvoiceService.php
 */

/* global closeModal, resetFormSubmitState */

// ==========================================
// PARSER MATA UANG
// ==========================================

/**
 * Parsing input mata uang sesuai format Indonesia.
 * - "1.000" => 1000 (titik sebagai pemisah ribuan)
 * - "Rp 1.000" => 1000
 *
 * @param  {string|number} value  Nilai input mentah
 * @return {number} Nilai numerik hasil parsing
 */
function parseCurrencyInput(value) {
    const str = String(value ?? '').trim();
    if (!str) return 0;

    const cleaned = str.replace(/Rp\s*/gi, '');

    if (cleaned.includes(',')) {
        const normalized = cleaned.replace(/\./g, '').replace(',', '.');
        const num = parseFloat(normalized);
        return Number.isFinite(num) ? num : 0;
    }

    const normalized = cleaned.replace(/\./g, '');
    const num = parseFloat(normalized);
    return Number.isFinite(num) ? num : 0;
}

/**
 * Format nilai input sebagai mata uang Indonesia (tanpa prefiks "Rp").
 *
 * @param  {HTMLInputElement} input  Element input yang akan diformat
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const str = String(input.value ?? '').trim();
    if (!str) return;

    const num = parseCurrencyInput(str);
    input.value = num ? Math.round(num).toLocaleString('id-ID') : '';
}

// Ekspos ke window untuk handler inline Blade (modal bukti pembayaran)
window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;

// ==========================================
// HAPUS MASSAL
// ==========================================

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

// ==========================================
// HAPUS INVOICE TUNGGAL
// ==========================================

/**
 * Hapus invoice proyek tunggal via hidden form submission.
 *
 * @param  {string} invoiceNumber  Nomor invoice yang akan dihapus
 */
function deleteInvoiceProyek(invoiceNumber) {
    closeModal('deleteModal-' + invoiceNumber);

    const form = document.getElementById('deleteForm-' + invoiceNumber);
    if (form) {
        form.submit();
    }
}

window.deleteInvoiceProyek = deleteInvoiceProyek;

// ==========================================
// PILIH SEMUA
// ==========================================

/**
 * Inisialisasi checkbox "Pilih Semua" untuk hapus massal.
 *
 * Tombol #delete-button dinonaktifkan bila tidak ada invoice dipilih.
 */
function initSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(invoiceCheckboxes).some(cb => cb.checked);
        if (deleteButton) deleteButton.disabled = !anyChecked;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            invoiceCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
            updateDeleteButtonState();
        });
    }

    invoiceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}

// ==========================================
// DOM SIAP
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    initSelectAllCheckbox();

    // Reset status submit saat halaman ditampilkan lagi (navigasi kembali/maju)
    window.addEventListener('pageshow', () => resetFormSubmitState());
});
