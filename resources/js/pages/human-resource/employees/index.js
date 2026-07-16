/**
 * Employee (Data Karyawan) page logic.
 *
 * Handles:
 * - Select All / Deselect All checkboxes
 * - Individual checkbox state management and delete button
 * - Bulk delete form submission with loading state
 * - Daily wage currency formatting on input
 * - Add/Edit form submit handling with double-submit prevention
 */

// ==========================================
// Currency Formatting for Daily Wage Input
// ==========================================

/**
 * Format an input field value as IDR currency (e.g., 150000 -> "150.000").
 * Strips all non-digit characters and re-formats.
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

// ==========================================
// Select All / Individual Checkboxes
// ==========================================

/**
 * Enable or disable the delete button based on how many checkboxes are selected.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

    if (checkedCheckboxes.length > 0) {
        deleteButton.disabled = false;
        deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.add('hover:bg-btn-delete-hover');
    } else {
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        deleteButton.classList.remove('hover:bg-btn-delete-hover');
    }
}

/**
 * Submit the bulk delete form with a loading spinner on the confirm button.
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
}

// ==========================================
// Event Listeners
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    window.submitDeleteForm = submitDeleteForm;

    // Select All Checkbox
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }, this);
            updateDeleteButtonState();
        });
    }

    // Individual Checkboxes
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            var selectAllCheckbox = document.getElementById('selectAll');

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    // Initialize delete button state
    updateDeleteButtonState();

    // Initialize searchable selects (from shared global)
    if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects();
    }

    // Daily Wage Currency Formatting
    document.querySelectorAll('.daily-wage-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // Add Form Submit (with double-submit prevention)
    var addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn)) {
                e.preventDefault();
            }
        });
    }

    // Edit Form Submits (with double-submit prevention)
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!window.handleFormSubmit(submitBtn)) {
                e.preventDefault();
            }
        });
    });
});
