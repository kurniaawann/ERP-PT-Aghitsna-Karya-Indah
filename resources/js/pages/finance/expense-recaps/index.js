/**
 * Rekap Pengeluaran — Modular JavaScript
 *
 * Fitur:
 * - Format currency input (Rupiah)
 * - Bulk delete (submit delete form)
 * - Select all checkbox
 * - Auto-submit filter form
 * - Print dropdown toggle
 * - Form submission handling (prevent double submit)
 * - Reset submit state on page show (back button)
 */

// ============================================================
// FORMAT CURRENCY INPUT
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
// INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // SELECT ALL CHECKBOX
    // ============================================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const expenseCheckboxes = document.querySelectorAll('input[name="selected_expenses[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update status disabled tombol hapus berdasarkan checkbox yang dipilih.
     */
    function updateDeleteButtonState() {
        const anyChecked = Array.from(expenseCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            expenseCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    expenseCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(expenseCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ============================================================
    // FORMAT CURRENCY INPUTS
    // ============================================================

    document.querySelectorAll('.expense-amount-input').forEach(input => {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // ============================================================
    // AUTO-SUBMIT FILTER FORM
    // ============================================================

    const categorySelect = document.getElementById('category-select');
    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');

    const filterForm = categorySelect ? categorySelect.closest('form') : null;

    if (categorySelect && filterForm) {
        categorySelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (monthSelect && filterForm) {
        monthSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (yearSelect && filterForm) {
        yearSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    // ============================================================
    // PRINT DROPDOWN
    // ============================================================

    const printDropdownButton = document.getElementById('printDropdownButton');
    const printDropdownMenu = document.getElementById('printDropdownMenu');

    if (printDropdownButton && printDropdownMenu) {
        printDropdownButton.addEventListener('click', function (e) {
            e.stopPropagation();
            printDropdownMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                printDropdownMenu.classList.add('hidden');
            }
        });

        const dropdownLinks = printDropdownMenu.querySelectorAll('a');
        dropdownLinks.forEach(link => {
            link.addEventListener('click', function () {
                printDropdownMenu.classList.add('hidden');
            });
        });
    }

    // ============================================================
    // FORM SUBMISSION HANDLING — ADD MODAL
    // ============================================================

    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ============================================================
    // FORM SUBMISSION HANDLING — EDIT MODALS
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
    // RESET SUBMIT STATE ON PAGE SHOW
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
