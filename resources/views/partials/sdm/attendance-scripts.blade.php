<script>
    // ==========================================
    // SELECT ALL ROW CHECKBOXES (Bulk Delete)
    // ==========================================

    /**
     * Select All checkbox - toggles all row checkboxes for bulk delete
     */
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = this.checked;
        }.bind(this));
        updateDeleteButtonState();
    });

    /**
     * Individual row checkbox - updates Select All state and delete button
     */
    document.querySelectorAll('input[name="ids[]"]').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('input[name="ids[]"]');
            var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateDeleteButtonState();
        });
    });

    /**
     * Update delete button state based on number of checked row checkboxes.
     * Enables the button only when at least one row is selected.
     */
    function updateDeleteButtonState() {
        var deleteButton = document.getElementById('delete-button');
        var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

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
     */
    function submitDeleteForm() {
        var deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }
        document.getElementById('deleteForm').submit();
    }

    // ==========================================
    // VALIDASI DUPLIKAT ABSENSI (CLIENT-SIDE)
    // ==========================================

    var existingAttendance = @json($existingAttendance ?? []);
    var duplicateWarning = document.getElementById('duplicate-warning');
    var duplicateWarningText = document.getElementById('duplicate-warning-text');
    var addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

    /**
     * Validate selected employees + date range against existing attendance data.
     * Shows a warning and disables submit if duplicates are found.
     *
     * @returns {boolean} true if no duplicates, false otherwise
     */
    function validateDuplicateAttendance() {
        var startDateInput = document.getElementById('start_date');
        var endDateInput = document.getElementById('end_date');

        // Get selected employee IDs from the multi-select hidden inputs
        var hiddenInputsContainer = document.querySelector('.searchable-multi-hidden-inputs');
        var hiddenInputs = hiddenInputsContainer ? hiddenInputsContainer.querySelectorAll('input[type="hidden"]') : [];
        var employeeIds = Array.from(hiddenInputs).map(function(input) { return input.value; });

        if (!startDateInput.value || !endDateInput.value || employeeIds.length === 0) {
            duplicateWarning.classList.add('hidden');
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
            duplicateWarning.classList.add('hidden');
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

    var addModalForm = document.querySelector('#addModal form');

    /**
     * Add form submit handler - validates employee selection and duplicate attendance
     * before allowing form submission.
     */
    if (addModalForm) {
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
    }

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

    // ==========================================
    // DATE VALIDATION
    // ==========================================

    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');

    /**
     * Date range validation:
     * - end_date minimum is set to start_date
     * - If end_date < start_date, auto-correct
     * - Re-validates duplicate attendance after date change
     */
    if (startDateInput && endDateInput) {
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
    document.querySelectorAll('[id^="editModal-"] form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ==========================================
    // INITIALIZATION
    // ==========================================

    // Initialize delete button state on page load
    updateDeleteButtonState();

    // Initialize searchable single-select components (used in edit modal)
    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
</script>
