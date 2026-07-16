/**
 * Overtime Index Page - JavaScript Module
 *
 * Handles all interactive functionality for the Data Lembur page:
 * - Searchable Select initialization
 * - Client-side duplicate attendance validation
 * - Select All checkbox logic
 * - Overtime total calculation (hours × rate)
 * - Delete button state management
 * - Form submit handlers with loading states
 */

// ==========================================
// SEARCHABLE SELECT INITIALIZATION
// ==========================================

/**
 * Initialize searchable select components on page load.
 * The searchable-select.js shared module provides the initSearchableSelects() function.
 * This re-initializes after DOM is ready to ensure all modals' selects work.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
});

// ==========================================
// CLIENT-SIDE DUPLICATE VALIDATION
// ==========================================

/**
 * Existing attendance data passed from PHP via Blade.
 * Structure: { 'EMP001': { '2025-01-01': { id: 1, status: 'hadir' }, ... }, ... }
 * Used to prevent:
 * - Duplicate overtime (same employee + date with status 'lembur')
 * - Overtime for employees with status izin/sakit/cuti
 */
const existingAttendance = window.overtimeExistingAttendance || {};

// --- Add Modal Duplicate Validation ---

/**
 * Validate the add overtime form for duplicate attendance.
 *
 * Checks if the selected employee already has an attendance record
 * for the selected date. If the existing record is 'lembur', it blocks
 * submission (duplicate). If it's 'izin', 'sakit', or 'cuti', it blocks
 * submission (overtime not allowed). If it's 'hadir', it allows submission.
 *
 * @returns {boolean} true if validation passes, false if blocked
 */
function validateAddOvertime() {
    const addEmployeeHidden = document.querySelector('#addModal .searchable-select-hidden');
    const addDateInput = document.getElementById('add-attendance-date');
    const addDuplicateWarning = document.getElementById('add-duplicate-warning');
    const addDuplicateWarningText = document.getElementById('add-duplicate-warning-text');
    const addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

    if (!addEmployeeHidden || !addDateInput) return true;

    const employeeId = addEmployeeHidden.value;
    const date = addDateInput.value;

    if (!employeeId || !date) {
        hideAddDuplicateWarning(addSubmitBtn);
        return true;
    }

    if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
        const existing = existingAttendance[employeeId][date];
        const employeeInput = document.querySelector('#addModal .searchable-select-input');
        const employeeName = employeeInput ? employeeInput.value : '';
        const formattedDate = formatDateIndonesian(date);

        if (existing.status === 'lembur') {
            showAddDuplicateWarning(
                addDuplicateWarning,
                addDuplicateWarningText,
                addSubmitBtn,
                `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau edit data yang sudah ada.`
            );
            return false;
        }

        if (['izin', 'sakit', 'cuti'].includes(existing.status)) {
            showAddDuplicateWarning(
                addDuplicateWarning,
                addDuplicateWarningText,
                addSubmitBtn,
                `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`
            );
            return false;
        }
    }

    hideAddDuplicateWarning(addSubmitBtn);
    return true;
}

/**
 * Show the duplicate warning banner in the add modal.
 *
 * @param {HTMLElement} warningEl     The warning container element
 * @param {HTMLElement} textEl        The text content element
 * @param {HTMLElement} submitBtn     The submit button to disable
 * @param {string}      message       The warning message to display
 */
function showAddDuplicateWarning(warningEl, textEl, submitBtn, message) {
    textEl.textContent = message;
    warningEl.classList.remove('hidden');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

/**
 * Hide the duplicate warning banner in the add modal.
 *
 * @param {HTMLElement} submitBtn  The submit button to re-enable
 */
function hideAddDuplicateWarning(submitBtn) {
    const addDuplicateWarning = document.getElementById('add-duplicate-warning');
    if (addDuplicateWarning) {
        addDuplicateWarning.classList.add('hidden');
    }
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

/**
 * Initialize add modal duplicate validation event listeners.
 * Uses MutationObserver to detect searchable-select hidden input changes.
 */
function initAddModalValidation() {
    const addEmployeeHidden = document.querySelector('#addModal .searchable-select-hidden');
    const addDateInput = document.getElementById('add-attendance-date');

    if (!addEmployeeHidden || !addDateInput) return;

    // Watch hidden input changes via MutationObserver (searchable-select updates hidden input)
    const searchableWrapper = addEmployeeHidden.closest('.searchable-select-wrapper');
    if (searchableWrapper) {
        const observer = new MutationObserver(function () {
            validateAddOvertime();
        });
        observer.observe(addEmployeeHidden, { attributes: true, attributeFilter: ['value'] });
    }

    addDateInput.addEventListener('change', validateAddOvertime);
}

// --- Edit Modal Duplicate Validation ---

/**
 * Initialize edit modal duplicate validation event listeners.
 * Each edit modal has its own date input with data attributes for tracking.
 */
function initEditModalValidation() {
    document.querySelectorAll('[id^="edit-attendance-date-"]').forEach(function (dateInput) {
        var overtimeId = dateInput.dataset.overtimeId;
        var originalDate = dateInput.dataset.originalDate;
        var employeeInput = dateInput.closest('form').querySelector('input[name="employee_id"]');
        var employeeId = employeeInput ? employeeInput.value : null;
        var duplicateWarning = document.getElementById('edit-duplicate-warning-' + overtimeId);
        var duplicateWarningText = document.getElementById('edit-duplicate-warning-text-' + overtimeId);
        var submitBtn = document.querySelector('#editModal-' + overtimeId + ' button[type="submit"]');

        function validateEditOvertime() {
            var date = dateInput.value;

            if (!date) {
                hideEditDuplicateWarning(duplicateWarning, submitBtn);
                return true;
            }

            // If the date hasn't changed from original, no validation needed
            if (date === originalDate) {
                hideEditDuplicateWarning(duplicateWarning, submitBtn);
                return true;
            }

            // Check if employee + date combination already exists
            if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
                var existing = existingAttendance[employeeId][date];

                // Skip if the record ID matches (it's the same record being edited)
                if (existing.id != overtimeId) {
                    var employeeName = dateInput.closest('form').querySelector('input[type="text"]').value;
                    var formattedDate = formatDateIndonesian(date);

                    if (existing.status === 'lembur') {
                        showEditDuplicateWarning(
                            duplicateWarning,
                            duplicateWarningText,
                            submitBtn,
                            `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau hapus data yang sudah ada.`
                        );
                        return false;
                    }

                    if (['izin', 'sakit', 'cuti'].includes(existing.status)) {
                        showEditDuplicateWarning(
                            duplicateWarning,
                            duplicateWarningText,
                            submitBtn,
                            `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`
                        );
                        return false;
                    }
                }
            }

            hideEditDuplicateWarning(duplicateWarning, submitBtn);
            return true;
        }

        dateInput.addEventListener('change', validateEditOvertime);
    });
}

/**
 * Show the duplicate warning banner in an edit modal.
 *
 * @param {HTMLElement} warningEl     The warning container element
 * @param {HTMLElement} textEl        The text content element
 * @param {HTMLElement} submitBtn     The submit button to disable
 * @param {string}      message       The warning message to display
 */
function showEditDuplicateWarning(warningEl, textEl, submitBtn, message) {
    textEl.textContent = message;
    warningEl.classList.remove('hidden');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

/**
 * Hide the duplicate warning banner in an edit modal.
 *
 * @param {HTMLElement} warningEl  The warning container element
 * @param {HTMLElement} submitBtn  The submit button to re-enable
 */
function hideEditDuplicateWarning(warningEl, submitBtn) {
    if (warningEl) {
        warningEl.classList.add('hidden');
    }
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

// ==========================================
// SELECT ALL CHECKBOX
// ==========================================

/**
 * Initialize select-all checkbox and individual checkbox listeners.
 * The select-all checkbox toggles all individual checkboxes.
 * Individual checkboxes update the select-all state and delete button.
 */
function initSelectAllCheckbox() {
    var selectAll = document.getElementById('selectAll');
    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        var checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
        updateDeleteButtonState();
    });

    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            selectAll.checked = allCheckboxes.length === checkedCheckboxes.length;
            updateDeleteButtonState();
        });
    });
}

// ==========================================
// OVERTIME TOTAL CALCULATION
// ==========================================

/**
 * Format an input field value as IDR currency (e.g., 15000 -> "15.000").
 * Strips all non-digit characters and re-formats with Indonesian locale.
 *
 * Assigned to window because it's called from inline oninput attributes
 * in the Blade templates (Vite loads JS as ES module, not global).
 *
 * @param {HTMLInputElement} input - The input element to format
 */
window.formatCurrencyInput = function (input) {
    if (!input) return;

    var numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
};

/**
 * Parse a formatted currency string back to a raw integer.
 * Handles Indonesian format with dots as thousands separator.
 *
 * @param  {string} value - Formatted currency string (e.g., "15.000")
 * @returns {number} Raw integer value (e.g., 15000)
 */
function parseCurrencyInput(value) {
    var rawValue = String(value || '').trim();

    if (!rawValue) {
        return 0;
    }

    return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
}

/**
 * Calculate overtime total for the add modal.
 * Parses formatted currency values and computes: hours × rate.
 * Updates the readonly total display field.
 *
 * Assigned to window because it's called from inline oninput attributes.
 */
window.calculateAddOvertimeTotal = function () {
    var addHoursInput = document.getElementById('add-overtime-hours');
    var addRateInput = document.getElementById('add-overtime-rate');
    var addTotalInput = document.getElementById('add-overtime-total');

    if (!addHoursInput || !addRateInput || !addTotalInput) return;

    var hours = parseFloat(addHoursInput.value) || 0;
    var rate = parseCurrencyInput(addRateInput.value);
    var total = hours * rate;

    addTotalInput.value = 'Rp ' + total.toLocaleString('id-ID');
};

/**
 * Calculate overtime total for a specific edit modal.
 * Parses formatted currency values and computes: hours × rate.
 *
 * Assigned to window because it's called from inline oninput attributes.
 *
 * @param {string} id - The overtime record ID to target the correct edit modal
 */
window.calculateEditOvertimeTotal = function (id) {
    var hoursInput = document.getElementById('edit-overtime-hours-' + id);
    var rateInput = document.getElementById('edit-overtime-rate-' + id);
    var totalInput = document.getElementById('edit-overtime-total-' + id);

    if (!hoursInput || !rateInput || !totalInput) return;

    var hours = parseFloat(hoursInput.value) || 0;
    var rate = parseCurrencyInput(rateInput.value);
    var total = hours * rate;

    totalInput.value = 'Rp ' + total.toLocaleString('id-ID');
};

// ==========================================
// DELETE BUTTON STATE
// ==========================================

/**
 * Update the delete button state based on checkbox selection.
 * Enables the button when at least one checkbox is checked,
 * disables it when no checkboxes are checked.
 */
function updateDeleteButtonState() {
    var deleteButton = document.getElementById('delete-button');
    var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

    if (!deleteButton) return;

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
 * Submit the bulk delete form with loading state.
 * Shows a loading spinner on the confirm button while the form is being submitted.
 *
 * Assigned to window because it's called from an inline onclick attribute
 * in the delete confirmation modal (Vite loads JS as ES module, not global).
 */
window.submitDeleteForm = function () {
    var deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
};

// ==========================================
// FORM SUBMIT HANDLERS
// ==========================================

/**
 * Initialize form submit handlers for add and edit modals.
 * Handles duplicate validation and loading state via handleFormSubmit().
 */
function initFormSubmitHandlers() {
    // Add modal submit handler
    var addOvertimeForm = document.querySelector('#addModal form');
    if (addOvertimeForm) {
        addOvertimeForm.addEventListener('submit', function (e) {
            if (!validateAddOvertime()) {
                e.preventDefault();
                return false;
            }

            var submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Edit modal submit handlers
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Format a date string to Indonesian locale format (dd/mm/yyyy).
 *
 * @param  {string}  dateStr  Date string in Y-m-d format
 * @returns {string} Formatted date string (dd/mm/yyyy)
 */
function formatDateIndonesian(dateStr) {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Initialize all overtime page functionality on DOM ready.
 */
document.addEventListener('DOMContentLoaded', function () {
    initAddModalValidation();
    initEditModalValidation();
    initSelectAllCheckbox();
    initFormSubmitHandlers();
    updateDeleteButtonState();
});
