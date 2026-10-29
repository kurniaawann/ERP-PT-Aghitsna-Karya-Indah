/**
 * Invoice Alumunium — JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Checkbox "Pilih Semua" & status tombol hapus massal (#delete-button)
 * - Submit form hapus massal dengan indikator loading
 * - Parser/format mata uang khusus alumunium (window.parseCurrencyInput /
 *   window.formatCurrencyInput) — tetap dipasang seperti sebelumnya karena
 *   dipakai field nominal di modal Upload Bukti Pembayaran.
 *
 * Form tambah/edit invoice kini berupa halaman tersendiri
 * (resources/js/pages/finance/aluminium-invoices/form.js).
 *
 * Referensi backend: app/Services/Finance/AlumuniumInvoiceService.php
 */

/* global resetFormSubmitState */

// ==========================================
// PARSER KHUSUS ALUMUNIUM
// ==========================================

/**
 * Parsing input mata uang sesuai format Indonesia:
 * - "1.000" => 1000 (titik sebagai pemisah ribuan)
 * - "1,5" => 1.5 (koma sebagai pemisah desimal)
 * - "Rp 1.000" => 1000
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
 */
function formatCurrencyInput(input) {
    const str = String(input.value ?? '').trim();
    if (!str) return;

    const num = parseCurrencyInput(str);
    input.value = num ? Math.round(num).toLocaleString('id-ID') : '';
}

window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;

// ==========================================
// BULK DELETE
// ==========================================

/**
 * Submit form hapus massal dengan indikator loading.
 *
 * Alur:
 * - Tampilkan spinner "Menghapus..." pada tombol konfirmasi dan nonaktifkan.
 * - Submit form #deleteForm (hanya invoice yang dicentang yang dikirim).
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
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    initSelectAllCheckbox();

    // Reset status submit saat halaman ditampilkan lagi (navigasi kembali/maju)
    window.addEventListener('pageshow', () => resetFormSubmitState());
});
