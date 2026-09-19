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
 * - submitPaymentProofDelete()
 *
 * Pada mode manual (proyek/recap), submit dijalankan via AJAX sehingga pesan
 * validasi (mis. nominal melebihi sisa tagihan) tampil di dalam modal tanpa
 * menutup modal maupun me-reload halaman.
 *
 * Dependensi global (dari layouts/app.blade.php / app.js bootstrap):
 * - openModal, closeModal, axios, showToast, parseCurrencyInput
 */

/* global openModal, closeModal, axios, showToast, parseCurrencyInput */

/**
 * Tampilkan/menyembunyikan pesan error di dalam modal upload.
 *
 * @param {string} message  Pesan error (kosong untuk menyembunyikan)
 */
function showUploadError(message) {
    const feedback = document.getElementById('payment-proof-feedback');
    if (feedback) {
        if (message) {
            feedback.textContent = message;
            feedback.classList.remove('hidden');
        } else {
            feedback.textContent = '';
            feedback.classList.add('hidden');
        }
    }

    const amountError = document.getElementById('payment-proof-amount-error');
    if (amountError) {
        if (message && /nominal/i.test(message)) {
            amountError.textContent = message;
            amountError.classList.remove('hidden');
        } else {
            amountError.textContent = '';
            amountError.classList.add('hidden');
        }
    }
}

/**
 * Membuka modal upload bukti pembayaran dengan konteks invoice yang sudah
 * diketahui (dari modal Edit / kolom aksi yang memicu).
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

    showUploadError('');

    // Tampilkan/sembunyikan input nominal tergantung tipe invoice.
    if (amountWrap && amountInput) {
        amountWrap.classList.toggle('hidden', !manualAmount);
        amountInput.value = '';
        if (manualAmount) {
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

/**
 * Submit hapus bukti pembayaran via AJAX (dipanggil tombol konfirmasi modal).
 */
function submitPaymentProofDelete() {
    const form = document.getElementById('payment-proof-delete-form');
    const confirmBtn = document.getElementById('confirm-btn-paymentProofDeleteModal');

    if (!form || (confirmBtn && confirmBtn.disabled)) {
        return;
    }

    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Menghapus...';
    }

    const formData = new FormData(form);

    axios
        .post(form.action, formData)
        .then(function (response) {
            if (typeof showToast === 'function') {
                showToast(response.data.message, 'success');
            }
            closeModal('paymentProofDeleteModal');
            setTimeout(function () {
                window.location.reload();
            }, 600);
        })
        .catch(function (error) {
            const data = error.response && error.response.data;
            const message = (data && (data.message || (data.errors && Object.values(data.errors)[0])))
                || 'Gagal menghapus bukti pembayaran. Silakan coba lagi.';
            if (typeof showToast === 'function') {
                showToast(message, 'error');
            } else if (typeof alert === 'function') {
                alert(message);
            }
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Ya, Hapus';
            }
        });
}

window.openPaymentProofUpload = openPaymentProofUpload;
window.openPaymentProofDelete = openPaymentProofDelete;
window.submitPaymentProofDelete = submitPaymentProofDelete;

/**
 * Bind submit upload modal via AJAX agar pesan validasi tampil di dalam modal.
 */
document.addEventListener('DOMContentLoaded', function () {
    const uploadForm = document.getElementById('payment-proof-upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && submitBtn.disabled) {
                return;
            }

            const invoiceTypeInput = document.getElementById('payment-proof-invoice-type');
            const manualAmount = (invoiceTypeInput && invoiceTypeInput.dataset.manualAmount) === '1';
            const amountInput = document.getElementById('payment-proof-amount');

            // Validasi nominal client-side untuk invoice proyek/recap.
            if (manualAmount && amountInput && parseCurrencyInput(amountInput.value) <= 0) {
                showUploadError('Nominal pembayaran harus lebih dari 0.');
                return;
            }

            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Menyimpan...';
            }

            const formData = new FormData(this);

            axios
                .post(this.action, formData)
                .then(function (response) {
                    showUploadError('');
                    if (typeof showToast === 'function') {
                        showToast(response.data.message, 'success');
                    }
                    uploadForm.reset();
                    closeModal('paymentProofUploadModal');
                    setTimeout(function () {
                        window.location.reload();
                    }, 600);
                })
                .catch(function (error) {
                    const data = error.response && error.response.data;
                    const message = (data && (data.message || (data.errors && Object.values(data.errors)[0])))
                        || 'Gagal menyimpan bukti pembayaran. Silakan coba lagi.';
                    showUploadError(message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                });
        });
    }
});