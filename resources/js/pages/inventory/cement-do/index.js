/**
 * DO Semen - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Format input currency Rupiah (harga & harga modal)
 * - Select all checkbox
 * - Bulk delete button state
 * - Loading state saat submit form modal tambah/edit
 */

/* global submitDeleteForm - dipanggil dari inline onclick pada Blade modal */
/**
 * Submit form hapus (bulk delete) dengan loading state pada tombol konfirmasi.
 *
 * Dipanggil dari inline onclick pada Blade modal. Menampilkan spinner
 * "Menghapus...", menonaktifkan tombol, lalu submit form deleteForm.
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
 * Mengikat event listener untuk format currency pada input harga.
 *
 * @param {HTMLInputElement} input
 */
function bindCurrencyInput(input) {
    input.addEventListener('input', function () {
        this.value = formatRupiah(parseCurrencyInput(this.value));
    });
}

/**
 * Mengikat pencegahan double-submit pada form modal tambah/edit.
 * Dipasang sekali per form.
 *
 * @param {HTMLFormElement|null} form Form modal (opsional)
 */
function bindFormSubmit(form) {
    if (form) {
        form.addEventListener('submit', function (e) {
            const submitBtnEl = form.querySelector('button[type="submit"]');
            const originalText = submitBtnEl.innerHTML;
            if (!handleFormSubmit(submitBtnEl, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }
}

/**
 * Inisialisasi halaman DO Semen.
 *
 * Alur:
 * 1. Modal tambah: input harga & harga modal diformat sebagai currency,
 *    dan form modal di-bind anti double-submit sekali.
 * 2. Modal edit (per item): setiap input harga & harga modal diformat,
 *    dan tiap form modal di-bind anti double-submit sekali.
 * 3. Checkbox select all & per-item mengontrol status tombol hapus bulk.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Format Harga & Anti Double-Submit pada Modal Tambah ──────────
    const addForm = document.querySelector('#addModal form');

    const addHarga = document.getElementById('add-harga');
    const addHargaModal = document.getElementById('add-harga-modal');

    if (addHarga) bindCurrencyInput(addHarga);
    if (addHargaModal) bindCurrencyInput(addHargaModal);
    bindFormSubmit(addForm);

    // ─── Format Harga & Anti Double-Submit pada Modal Edit (per item) ─
    const editHargaInputs = document.querySelectorAll('[id^="edit-harga-"]:not([id^="edit-harga-modal-"])');

    editHargaInputs.forEach(function (hargaInput) {
        const itemNo = hargaInput.id.replace('edit-harga-', '');
        bindCurrencyInput(hargaInput);
        bindFormSubmit(document.querySelector('#editModal-' + itemNo + ' form'));
    });

    const editHargaModalInputs = document.querySelectorAll('[id^="edit-harga-modal-"]');

    editHargaModalInputs.forEach(function (hargaModalInput) {
        const itemNo = hargaModalInput.id.replace('edit-harga-modal-', '');
        bindCurrencyInput(hargaModalInput);
    });

    // ─── Select All Checkbox ──────────────────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Mengupdate status tombol hapus berdasarkan checkbox yang dipilih.
     */
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

    updateDeleteButtonState();
});
