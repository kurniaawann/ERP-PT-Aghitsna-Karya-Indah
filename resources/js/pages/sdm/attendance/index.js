/**
 * Attendance Index Page - JavaScript Module
 *
 * Handles all interactive functionality for the Data Absensi page:
 * - Select All checkbox & bulk delete
 * - Client-side duplicate attendance validation
 * - Add form validation (employee selection + date range)
 * - Date range validation with auto-correct
 * - Edit form submit handlers
 *
 * Server data is passed via window.attendanceConfig (set in pages/sdm/attendance.blade.php).
 * Functions called from inline HTML attributes are exposed on window
 * because Vite loads JS as ES module, not global.
 */

const config = window.attendanceConfig || {};

const existingAttendance = config.existingAttendance || {};

// ==========================================
// SELECT ALL ROW CHECKBOXES (Bulk Delete)
// ==========================================

/**
 * Update delete button state based on number of checked row checkboxes.
 * Enables the button only when at least one row is selected.
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
 * Submit the bulk delete form with loading state on the confirm button.
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
// VALIDASI DUPLIKAT ABSENSI (CLIENT-SIDE)
// ==========================================

/**
 * Validate selected employees + date range against existing attendance data.
 * Shows a warning and disables submit if duplicates are found.
 *
 * @returns {boolean} true if no duplicates, false otherwise
 */
function validateDuplicateAttendance() {
    var duplicateWarning = document.getElementById('duplicate-warning');
    var duplicateWarningText = document.getElementById('duplicate-warning-text');
    var addSubmitBtn = document.querySelector('#addModal button[type="submit"]');
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');

    // Get selected employee IDs from the multi-select hidden inputs
    var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
    var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];
    var employeeIds = Array.from(hiddenInputs).map(function(input) { return input.value; });

    if (!startDateInput || !endDateInput || !startDateInput.value || !endDateInput.value || employeeIds.length === 0) {
        if (duplicateWarning) {
            duplicateWarning.classList.add('hidden');
        }
        if (addSubmitBtn) {
            addSubmitBtn.disabled = false;
            addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }

    var startDate = new Date(startDateInput.value);
    var endDate = new Date(endDateInput.value);
    var duplicates = [];

    // Get employee labels from the multi-select wrapper for error messages
    var wrapper = document.querySelector('.searchable-multi-select-wrapper');
    var optionElements = wrapper ? wrapper.querySelectorAll('.searchable-multi-options .searchable-multi-option') : [];

    employeeIds.forEach(function(employeeId) {
        // Find the label for this employee
        var employeeName = employeeId;
        optionElements.forEach(function(opt) {
            if (opt.dataset.value === employeeId) {
                employeeName = opt.dataset.label.split(' - ')[0];
            }
        });

        if (existingAttendance[employeeId]) {
            var currentDate = new Date(startDate);
            while (currentDate <= endDate) {
                var dateStr = currentDate.toISOString().split('T')[0];

                if (existingAttendance[employeeId].indexOf(dateStr) !== -1) {
                    var formattedDate = new Date(dateStr).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    duplicates.push(employeeName + ' pada tanggal ' + formattedDate);
                }

                currentDate.setDate(currentDate.getDate() + 1);
            }
        }
    });

    if (duplicates.length > 0) {
        var displayDuplicates = duplicates.slice(0, 5);
        var message = 'Karyawan berikut sudah memiliki absensi: ' + displayDuplicates.join('; ');

        if (duplicates.length > 5) {
            message += ' dan ' + (duplicates.length - 5) + ' lainnya';
        }

        message += '. Silakan hapus atau edit data yang sudah ada.';

        duplicateWarningText.textContent = message;
        duplicateWarning.classList.remove('hidden');

        if (addSubmitBtn) {
            addSubmitBtn.disabled = true;
            addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        return false;
    } else {
        if (duplicateWarning) {
            duplicateWarning.classList.add('hidden');
        }
        if (addSubmitBtn) {
            addSubmitBtn.disabled = false;
            addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }
}

// ==========================================
// FORM VALIDATION
// ==========================================

/**
 * Add form submit handler - validates employee selection and duplicate attendance
 * before allowing form submission.
 */
function initAddFormHandler() {
    var addModalForm = document.querySelector('#addModal form');
    if (!addModalForm) return;

    addModalForm.addEventListener('submit', function(e) {
        // Validate at least 1 employee is selected
        var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
        var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];

        if (hiddenInputs.length === 0) {
            e.preventDefault();
            var multiSelectError = document.querySelector('.searchable-multi-error');
            if (multiSelectError) {
                multiSelectError.classList.remove('hidden');
            }
            return false;
        }

        // Validate no duplicate attendance
        if (!validateDuplicateAttendance()) {
            e.preventDefault();
            return false;
        }

        // Apply loading state to submit button
        var submitBtn = this.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn)) {
            e.preventDefault();
            return false;
        }
    });

    // Hide multi-select error when employee is selected
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('searchable-multi-checkbox') || e.target.classList.contains('searchable-multi-select-all')) {
            var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
            var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];
            var multiSelectError = document.querySelector('.searchable-multi-error');

            if (hiddenInputs.length > 0 && multiSelectError) {
                multiSelectError.classList.add('hidden');
            }
        }
    });
}

// ==========================================
// DATE VALIDATION
// ==========================================

/**
 * Date range validation:
 * - end_date minimum is set to start_date
 * - If end_date < start_date, auto-correct
 * - Re-validates duplicate attendance after date change
 */
function initDateValidation() {
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');

    if (!startDateInput || !endDateInput) return;

    startDateInput.addEventListener('change', function() {
        var dateError = document.getElementById('date-error');
        endDateInput.min = this.value;

        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
        if (dateError) {
            dateError.classList.add('hidden');
        }
        validateDuplicateAttendance();
    });

    endDateInput.addEventListener('change', function() {
        var dateError = document.getElementById('date-error');
        if (startDateInput.value && this.value < startDateInput.value) {
            if (dateError) {
                dateError.classList.remove('hidden');
            }
            this.value = startDateInput.value;
        } else {
            if (dateError) {
                dateError.classList.add('hidden');
            }
        }
        validateDuplicateAttendance();
    });
}

// ==========================================
// EDIT MODAL FORM HANDLERS
// ==========================================

/**
 * Edit form submit handlers - applies loading state during form submission.
 */
function initEditFormHandlers() {
    document.querySelectorAll('[id^="editModal-"] form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Initialize all attendance page functionality on DOM ready.
 */
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAll');

    if (selectAll) {
        // Select All checkbox - toggles all row checkboxes for bulk delete
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateDeleteButtonState();
        });
    }

    // Individual row checkbox - updates Select All state and delete button
    document.querySelectorAll('input[name="ids[]"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateDeleteButtonState();
        });
    });

    // Initialize delete button state on page load
    updateDeleteButtonState();

    initAddFormHandler();
    initDateValidation();
    initEditFormHandlers();

    // Initialize searchable single-select components (used in edit modal)
    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
});
