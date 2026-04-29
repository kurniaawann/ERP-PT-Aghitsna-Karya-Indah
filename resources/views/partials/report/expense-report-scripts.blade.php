<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    let isSubmitting = false;

    function handleFormSubmit(submitBtn, originalText) {
        if (isSubmitting) return false;

        isSubmitting = true;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        return true;
    }

    // ==========================================
    // BULK DELETE FUNCTION
    // ==========================================

    function submitDeleteForm() {
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        const form = document.getElementById('deleteForm');
        if (!form) {
            console.error('Delete form not found');
            return false;
        }

        // Submit the form
        form.submit();
        return true;
    }

    // ==========================================
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // SELECT ALL CHECKBOX FUNCTIONALITY
        // ==========================================

        const selectAllCheckbox = document.getElementById('selectAll');
        const expenseCheckboxes = document.querySelectorAll('input[name="selected_expenses[]"]');
        const deleteButton = document.getElementById('delete-button');

        // Function to update delete button state
        function updateDeleteButtonState() {
            const anyChecked = Array.from(expenseCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                expenseCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        // Uncheck "Select All" if any individual checkbox is unchecked
        expenseCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(expenseCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        // Initialize button state on page load
        updateDeleteButtonState();

        // ==========================================
        // AUTO-SUBMIT FILTER FORM
        // ==========================================

        const categorySelect = document.getElementById('category-select');
        const monthSelect = document.getElementById('month-select');
        const yearSelect = document.getElementById('year-select');
        const typeSelect = document.getElementById('type-select');

        // Find the filter form
        const filterForm = categorySelect ? categorySelect.closest('form') : null;

        if (categorySelect && filterForm) {
            categorySelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        if (monthSelect && filterForm) {
            monthSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        if (yearSelect && filterForm) {
            yearSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        if (typeSelect && filterForm) {
            typeSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        // ==========================================
        // PRINT DROPDOWN FUNCTIONALITY
        // ==========================================

        const printDropdownButton = document.getElementById('printDropdownButton');
        const printDropdownMenu = document.getElementById('printDropdownMenu');

        if (printDropdownButton && printDropdownMenu) {
            printDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                printDropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                    printDropdownMenu.classList.add('hidden');
                }
            });

            // Close dropdown when clicking on a menu item
            const dropdownLinks = printDropdownMenu.querySelectorAll('a');
            dropdownLinks.forEach(link => {
                link.addEventListener('click', function() {
                    printDropdownMenu.classList.add('hidden');
                });
            });
        }

        // ==========================================
        // FORM SUBMISSION HANDLING FOR ADD MODAL
        // ==========================================

        const addForm = document.querySelector('#addModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';

                if (!handleFormSubmit(submitBtn, originalText)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // ==========================================
        // FORM SUBMISSION HANDLING FOR EDIT MODALS
        // ==========================================

        const editForms = document.querySelectorAll('[id^="editModal-"] form');
        editForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';

                if (!handleFormSubmit(submitBtn, originalText)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // ==========================================
        // RESET isSubmitting FLAG ON PAGE SHOW
        // ==========================================

        window.addEventListener('pageshow', function(event) {
            // Reset submitting flag when navigating back
            isSubmitting = false;

            // Re-enable all submit buttons
            const allSubmitButtons = document.querySelectorAll('button[type="submit"]');
            allSubmitButtons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');

                // Try to restore original text
                const modalId = btn.closest('[id^="Modal"]')?.id;
                if (modalId) {
                    if (modalId === 'addModal') {
                        btn.innerHTML = 'Simpan';
                    } else if (modalId.startsWith('editModal-')) {
                        btn.innerHTML = 'Update';
                    }
                }
            });
        });
    });
</script>
