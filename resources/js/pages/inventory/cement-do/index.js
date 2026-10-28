/**
 * DO Semen - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Modal "Buat Invoice Semen" per baris DO (checkbox data semen,
 *   preview total, select all, guard jumlah minimal)
 * - Select all checkbox
 * - Bulk delete button state
 *
 * Form tambah/edit DO kini halaman tersendiri (cement-do/form.js).
 */

/* global submitDeleteForm - dipanggil dari inline onclick pada Blade modal */
/**
 * Submit form hapus (bulk delete) dengan loading state pada tombol konfirmasi.
 *
 * @returns {void}
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

/**
 * Batalkan submit bila tidak ada data semen yang dicentang, lalu
 * terapkan loading state pada tombol submit (anti double-submit).
 *
 * @param {HTMLFormElement} form
 * @param {HTMLElement} modal Wrapper modal (id `generateInvoiceModal-{no}`).
 */
function bindGenerateInvoiceSubmit(form, modal) {
    form.addEventListener('submit', function (e) {
        if (modal.querySelectorAll('.gen-inv-cement:checked').length === 0) {
            e.preventDefault();
            if (typeof showToast === 'function') {
                showToast('Pilih minimal satu data semen untuk diinvois.', 'error');
            }
            return false;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn)) {
            e.preventDefault();
            return false;
        }
    });
}

/**
 * Perbarui preview total & status tombol submit pada modal
 * "Buat Invoice Semen" untuk satu DO.
 *
 * @param {HTMLElement} modal Wrapper modal (id `generateInvoiceModal-{no}`).
 */
function updateGenerateInvoicePreview(modal) {
    if (!modal) return;

    let total = 0;
    modal.querySelectorAll('.gen-inv-cement:checked').forEach(function (cb) {
        total += (Number(cb.dataset.total) || 0);
    });

    const totalEl = modal.querySelector('.gen-inv-total');
    if (totalEl) totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');

    const submitBtn = modal.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = total <= 0;
}

/**
 * Buka modal "Buat Invoice Semen" untuk satu baris DO dan hitung ulang
 * preview total. Dipanggil dari onclick tombol di tabel.
 *
 * @param {string} no Nomor DO Semen (mis. "DOS-202601-001").
 */
window.openGenerateInvoiceModal = function (no) {
    if (typeof openModal === 'function') {
        openModal('generateInvoiceModal-' + no);
    }
    updateGenerateInvoicePreview(document.getElementById('generateInvoiceModal-' + no));
};

/**
 * Inisialisasi halaman DO Semen.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Select All Checkbox ──────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(function (cb) {
            return cb.checked;
        });
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            itemCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    itemCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(itemCheckboxes).every(function (cb) {
                    return cb.checked;
                });
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    // ─── Modal "Buat Invoice Semen" per DO ────────────────────────
    document.querySelectorAll('[id^="generateInvoiceModal-"]').forEach(function (modal) {
        // Perbarui preview saat checkbox berubah.
        modal.querySelectorAll('.gen-inv-cement').forEach(function (cb) {
            cb.addEventListener('change', function () {
                updateGenerateInvoicePreview(modal);
            });
        });

        // Tombol "Centang Semua".
        const selectAllGenBtn = modal.querySelector('.gen-select-all');
        if (selectAllGenBtn) {
            selectAllGenBtn.addEventListener('click', function () {
                modal.querySelectorAll('.gen-inv-cement').forEach(function (cb) {
                    cb.checked = true;
                });
                updateGenerateInvoicePreview(modal);
            });
        }

        // Guard submit.
        const genForm = modal.querySelector('form');
        if (genForm) bindGenerateInvoiceSubmit(genForm, modal);

        updateGenerateInvoicePreview(modal);
    });

    updateDeleteButtonState();
});
