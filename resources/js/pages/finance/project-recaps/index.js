/**
 * Rekap Proyek — Modular JavaScript
 *
 * Fitur:
 * - Format input currency (Rupiah) untuk Total RAB
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

/**
 * Bersihkan format Rupiah (titik ribuan) menjadi angka polos sebelum
 * dikirim ke server, sehingga validasi 'numeric' di backend lolos.
 * @param {HTMLFormElement} form
 */
function sanitizeCurrencyInputs(form) {
    form.querySelectorAll('.total-rab-input').forEach(input => {
        input.value = String(input.value ?? '').replace(/[^\d]/g, '');
    });
}

/**
 * Submit bulk delete form dengan loading indicator.
 */
function submitDeleteForm() {
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
}
window.submitDeleteForm = submitDeleteForm;

// ============================================================
// INISIALISASI
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // CHECKBOX PILIH SEMUA
    // ============================================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const recapCheckboxes = document.querySelectorAll('input[name="selected_recaps[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update status disabled tombol hapus berdasarkan checkbox yang dipilih.
     */
    function updateDeleteButtonState() {
        const anyChecked = Array.from(recapCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            recapCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    recapCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(recapCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ============================================================
    // FORMAT INPUT CURRENCY (TOTAL RAB)
    // ============================================================

    document.querySelectorAll('.total-rab-input').forEach(input => {
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

    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            sanitizeCurrencyInputs(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL EDIT
    // ============================================================

    const editForms = document.querySelectorAll('[id^="editModal-"] form');
    editForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            sanitizeCurrencyInputs(this);
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
