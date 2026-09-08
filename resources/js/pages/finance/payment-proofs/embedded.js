/**
 * Payment Proof Embedded (managed from module edit modals)
 *
 * Menghandle bukti pembayaran yang terintegrasi di dalam modal Edit tiap
 * modul (Invoice Proyek, Invoice Alumunium, Rekap Proyek, Invoice Barang,
 * Rekap Penjualan). Alih-alih mengupload lewat halaman mandiri, pengguna
 * menekan tombol "Upload" / "Hapus" di dalam modal Edit yang memicu modal
 * terpisah (menghindari nested <form>).
 *
 * Ekspos ke window:
 * - openPaymentProofUpload(invoiceType, invoiceNumber, manualAmount)
 * - openPaymentProofDelete(proofId)
 *
 * Dependensi global (dari layouts/app.blade.php): openModal, closeModal.
 */

/* global openModal, closeModal */

/**
 * Membuka modal upload bukti pembayaran dengan konteks invoice yang sudah
 * diketahui (dari modal Edit yang memicu).
 *
 * @param {string} invoiceType     invoice_type: proyek|alumunium|barang|recap|rekap_penjualan
 * @param {string} invoiceNumber   nilai invoice_number / id yang disimpan
 * @param {boolean} manualAmount   true bila nominal diisi manual (proyek/recap)
 */
function openPaymentProofUpload(invoiceType, invoiceNumber, manualAmount) {
    const invoiceTypeInput = document.getElementById('payment-proof-invoice-type');
    const invoiceNumberInput = document.getElementById('payment-proof-invoice-number');
    const amountWrap = document.getElementById('payment-proof-amount-wrap');
    const amountInput = document.getElementById('payment-proof-amount');

    if (!invoiceTypeInput || !invoiceNumberInput) {
        return;
    }

    invoiceTypeInput.value = invoiceType;
    invoiceNumberInput.value = invoiceNumber;
    invoiceTypeInput.dataset.manualAmount = manualAmount ? '1' : '0';

    const label = document.getElementById('payment-proof-invoice-label');
    if (label) {
        label.textContent = invoiceNumber;
    }

    // Tampilkan/sembunyikan input nominal tergantung tipe invoice.
    if (amountWrap && amountInput) {
        amountWrap.classList.toggle('hidden', !manualAmount);
        if (!manualAmount) {
            amountInput.value = 'Rp 0';
        } else {
            amountInput.value = 'Rp 0';
            amountInput.focus();
        }
    }

    openModal('paymentProofUploadModal');
}

/**
 * Membuka modal konfirmasi hapus bukti pembayaran.
 * Action form hapus di-set ke route destroy untuk proof yang bersangkutan.
 *
 * @param {string} proofId  id bukti pembayaran yang akan dihapus
 */
function openPaymentProofDelete(proofId) {
    const form = document.getElementById('payment-proof-delete-form');
    if (!form) {
        return;
    }

    const baseUrl = form.dataset.baseUrl || form.getAttribute('action');
    form.action = baseUrl.replace('__ID__', encodeURIComponent(proofId));

    openModal('paymentProofDeleteModal');
}

window.openPaymentProofUpload = openPaymentProofUpload;
window.openPaymentProofDelete = openPaymentProofDelete;

/**
 * Bind submit upload modal agar tidak terjadi double-submit.
 */
document.addEventListener('DOMContentLoaded', function () {
    const uploadForm = document.getElementById('payment-proof-upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function') {
                if (!handleFormSubmit(submitBtn, submitBtn.innerHTML, 'Menyimpan...')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    const deleteForm = document.getElementById('payment-proof-delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function') {
                if (!handleFormSubmit(submitBtn, submitBtn.innerHTML, 'Menghapus...')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
