/**
 * Laporan Keuangan Proyek — Modular JavaScript
 *
 * Fitur:
 * - Format input currency (Rupiah)
 * - Sinkron label "Jumlah Pemasukan / Jumlah Pengeluaran" berdasarkan tipe kategori
 * - Hapus massal (submit form hapus)
 * - Checkbox pilih semua
 * - Penanganan submit form (cegah double submit)
 * - Reset state submit saat halaman dimuat ulang (tombol kembali)
 */

// ============================================================
// FORMAT INPUT CURRENCY
// ============================================================

/**
 * Format input currency ke format Indonesia (Rp X.XXX).
 * @param {HTMLInputElement} input
 */
function formatCurrencyInput(input) {
    if (!input) return;
    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}
window.formatCurrencyInput = formatCurrencyInput;

// ============================================================
// KATEGORI INCOME vs EXPENSE — LABEL JUMLAH
// ============================================================

/**
 * Sinkronkan form (tambah/edit) berdasarkan tipe kategori terpilih.
 * - INCOME: label "Jumlah Pemasukan"
 * - EXPENSE: label "Jumlah Pengeluaran"
 *
 * @param {HTMLFormElement} form
 */
function syncCategoryFields(form) {
    const select = form.querySelector('select[name="transaction_category_id"]');
    if (!select) return;

    const selected = select.options[select.selectedIndex];
    const isIncome = selected && selected.dataset.type === 'INCOME';

    const amountLabel = form.querySelector('.amount-label');
    if (amountLabel) {
        amountLabel.innerHTML = (isIncome ? 'Jumlah Pemasukan' : 'Jumlah Pengeluaran') +
            ' <span class="text-error">*</span>';
    }
}
window.syncCategoryFields = syncCategoryFields;

/**
 * Submit bulk delete form dengan loading indicator.
 *
 * @param {string} [modalId] ID modal konfirmasi hapus (memuat tombol konfirmasi).
 * @param {string} [formId]  ID form hapus massal yang akan di-submit.
 */
function submitDeleteForm(modalId = 'deleteModal', formId = 'deleteForm') {
    const deleteBtn = document.getElementById('confirm-btn-' + modalId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
}
window.submitDeleteForm = submitDeleteForm;

// ============================================================
// INISIALISASI
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // KATEGORI INCOME vs EXPENSE — SINKRON LABEL FORM
    // ============================================================

    document.querySelectorAll('#addModal form, [id^="addModal-"] form, [id^="editModal-"] form').forEach(function (form) {
        const select = form.querySelector('select[name="transaction_category_id"]');
        if (select) {
            syncCategoryFields(form);
            select.addEventListener('change', function () {
                syncCategoryFields(form);
            });
        }
    });

    // ============================================================
    // CHECKBOX PILIH SEMUA (per form hapus massal)
    // ============================================================

    document.querySelectorAll('form[id="deleteForm"], form[id^="deleteForm-"]').forEach(function (form) {
        const suffix = form.id === 'deleteForm' ? '' : form.id.slice('deleteForm'.length);
        const selectAllCheckbox = form.querySelector('#selectAll' + suffix);
        const itemCheckboxes = form.querySelectorAll('input[name="selected_items[]"], input[name="selected_recaps[]"]');
        const deleteButton = document.getElementById('delete-button' + suffix);

        function updateDeleteButtonState() {
            const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();
    });

    // ============================================================
    // FORMAT INPUT CURRENCY
    // ============================================================

    document.querySelectorAll('.expense-amount-input, .total-rab-input').forEach(input => {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL TAMBAH
    // ============================================================

    const addForms = document.querySelectorAll('#addModal form, [id^="addModal-"] form');
    addForms.forEach(function (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL EDIT
    // ============================================================

    const editForms = document.querySelectorAll('[id^="editModal-"] form');
    editForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // RESET STATE SUBMIT SAAT HALAMAN DIMUAT ULANG
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
