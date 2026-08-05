/**
 * Data Barang - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Validasi harga modal < harga jual
 * - Format input currency Rupiah
 * - Select all checkbox
 * - Bulk delete button state
 * - Print dropdown toggle
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
 * Memvalidasi pasangan harga modal dan harga jual.
 * Harga modal tidak boleh lebih besar atau sama dengan harga jual.
 *
 * Alur:
 * 1. Parse nilai kedua input (currency string → angka) via
 *    parseCurrencyInput().
 * 2. Tandai invalid bila capitalPrice >= sellingPrice dan sellingPrice > 0
 *    (harga modal harus lebih kecil dari harga jual).
 * 3. Bila invalid: tampilkan elemen warning, nonaktifkan tombol submit
 *    (opacity + cursor-not-allowed), kembalikan false.
 * 4. Bila valid: sembunyikan warning, aktifkan tombol submit, true.
 *
 * @param {HTMLInputElement} capitalInput  Input harga modal
 * @param {HTMLInputElement} sellingInput  Input harga jual
 * @param {HTMLElement}      warningEl     Elemen warning yang ditampilkan/disembunyikan
 * @param {HTMLElement|null} submitBtn     Tombol submit yang dinonaktifkan
 * @returns {boolean} true jika valid
 */
function validatePricePair(capitalInput, sellingInput, warningEl, submitBtn) {
    const capitalPrice = parseCurrencyInput(capitalInput.value);
    const sellingPrice = parseCurrencyInput(sellingInput.value);
    const isInvalid = capitalPrice >= sellingPrice && sellingPrice > 0;

    if (isInvalid) {
        warningEl.classList.remove('hidden');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    } else {
        warningEl.classList.add('hidden');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    return !isInvalid;
}

/**
 * Mengikat event listener untuk format currency pada input.
 *
 * @param {HTMLInputElement} input
 */
function bindCurrencyInput(input) {
    input.addEventListener('input', function () {
        this.value = formatRupiah(parseCurrencyInput(this.value));
    });
}

/**
 * Mengikat validasi harga dan format currency pada pasangan input.
 *
 * Alur:
 * 1. Bind format currency pada kedua input via bindCurrencyInput().
 * 2. Setiap input pada harga modal/jual memicu validatePricePair() untuk
 *    menampilkan warning dan mengontrol tombol submit secara real-time.
 * 3. Saat form disubmit, validasi dijalankan ulang; bila tidak valid,
 *    submit dibatalkan dan warning discroll ke tengah layar.
 * 4. Bila valid, handleFormSubmit() mencegah double-submit (spinner).
 *
 * @param {HTMLInputElement} capitalInput  Input harga modal
 * @param {HTMLInputElement} sellingInput  Input harga jual
 * @param {HTMLElement}      warningEl     Elemen warning harga
 * @param {HTMLElement|null} submitBtn     Tombol submit modal
 * @param {HTMLFormElement|null} form      Form modal (opsional)
 */
function bindPriceValidation(capitalInput, sellingInput, warningEl, submitBtn, form) {
    bindCurrencyInput(capitalInput);
    bindCurrencyInput(sellingInput);

    capitalInput.addEventListener('input', function () {
        validatePricePair(capitalInput, sellingInput, warningEl, submitBtn);
    });

    sellingInput.addEventListener('input', function () {
        validatePricePair(capitalInput, sellingInput, warningEl, submitBtn);
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validatePricePair(capitalInput, sellingInput, warningEl, submitBtn)) {
                e.preventDefault();
                warningEl.classList.remove('hidden');
                warningEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

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
 * Inisialisasi halaman Data Barang.
 *
 * Alur:
 * 1. Modal tambah: pasangan input harga modal/jual divalidasi via
 *    bindPriceValidation() dengan elemen warning & tombol submit.
 * 2. Modal edit (per item): untuk setiap input harga modal, cari pasangan
 *    harga jual, warning, tombol submit, dan form, lalu bind validasi.
 * 3. Checkbox select all & per-item mengontrol status tombol hapus bulk.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Validasi Harga pada Modal Tambah ───────────────────────────────
    const addCapitalPrice = document.getElementById('add-capital-price');
    const addSellingPrice = document.getElementById('add-selling-price');
    const addPriceWarning = document.getElementById('add-price-warning');
    const addSubmitBtn = document.getElementById('submit-btn-addModal');
    const addForm = document.querySelector('#addModal form');

    if (addCapitalPrice && addSellingPrice) {
        bindPriceValidation(addCapitalPrice, addSellingPrice, addPriceWarning, addSubmitBtn, addForm);
    }

    // ─── Validasi Harga pada Modal Edit (per item) ─────────────────────
    const editCapitalPriceInputs = document.querySelectorAll('[id^="edit-capital-price-"]');

    editCapitalPriceInputs.forEach(function (capitalInput) {
        const itemId = capitalInput.id.replace('edit-capital-price-', '');
        const sellingInput = document.getElementById('edit-selling-price-' + itemId);
        const warningEl = document.getElementById('edit-price-warning-' + itemId);
        const editSubmitBtn = document.getElementById('submit-btn-editModal-' + itemId);
        const editForm = document.querySelector('#editModal-' + itemId + ' form');

        if (sellingInput) {
            bindPriceValidation(capitalInput, sellingInput, warningEl, editSubmitBtn, editForm);
        }
    });

    // ─── Select All Checkbox ────────────────────────────────────────────
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
