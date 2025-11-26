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
        const form = document.getElementById('deleteForm');
        if (form) {
            form.submit();
        }
    }

    // ==========================================
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // PRICE VALIDATION FOR ADD MODAL
        // ==========================================

        const addCapitalPrice = document.getElementById('add-capital-price');
        const addSellingPrice = document.getElementById('add-selling-price');
        const addPriceWarning = document.getElementById('add-price-warning');
        const addSubmitBtn = document.getElementById('submit-btn-addModal');

        function validateAddModalPrices() {
            const capitalPrice = parseFloat(addCapitalPrice.value) || 0;
            const sellingPrice = parseFloat(addSellingPrice.value) || 0;

            if (capitalPrice >= sellingPrice && sellingPrice > 0) {
                addPriceWarning.classList.remove('hidden');
                // Disable submit button
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = true;
                    addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else {
                addPriceWarning.classList.add('hidden');
                // Enable submit button
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = false;
                    addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return true;
            }
        }

        if (addCapitalPrice && addSellingPrice) {
            addCapitalPrice.addEventListener('input', validateAddModalPrices);
            addSellingPrice.addEventListener('input', validateAddModalPrices);

            // Prevent form submission if validation fails
            const addForm = document.querySelector('#addModal form');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    if (!validateAddModalPrices()) {
                        e.preventDefault();
                        addPriceWarning.classList.remove('hidden');
                        // Scroll to warning
                        addPriceWarning.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        return false;
                    }

                    // Add loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    if (!handleFormSubmit(submitBtn, originalText)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        }

        // ==========================================
        // PRICE VALIDATION FOR EDIT MODALS
        // ==========================================

        // Get all edit modal price inputs
        const editCapitalPriceInputs = document.querySelectorAll('[id^="edit-capital-price-"]');
        const editSellingPriceInputs = document.querySelectorAll('[id^="edit-selling-price-"]');

        editCapitalPriceInputs.forEach(function(capitalPriceInput) {
            const itemId = capitalPriceInput.id.replace('edit-capital-price-', '');
            const sellingPriceInput = document.getElementById('edit-selling-price-' + itemId);
            const priceWarning = document.getElementById('edit-price-warning-' + itemId);
            const editSubmitBtn = document.getElementById('submit-btn-editModal-' + itemId);

            function validateEditModalPrices() {
                const capitalPrice = parseFloat(capitalPriceInput.value) || 0;
                const sellingPrice = parseFloat(sellingPriceInput.value) || 0;

                if (capitalPrice >= sellingPrice && sellingPrice > 0) {
                    priceWarning.classList.remove('hidden');
                    // Disable submit button
                    if (editSubmitBtn) {
                        editSubmitBtn.disabled = true;
                        editSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    priceWarning.classList.add('hidden');
                    // Enable submit button
                    if (editSubmitBtn) {
                        editSubmitBtn.disabled = false;
                        editSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    return true;
                }
            }

            capitalPriceInput.addEventListener('input', validateEditModalPrices);
            sellingPriceInput.addEventListener('input', validateEditModalPrices);

            // Prevent form submission if validation fails
            const editForm = document.querySelector('#editModal-' + itemId + ' form');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    if (!validateEditModalPrices()) {
                        e.preventDefault();
                        priceWarning.classList.remove('hidden');
                        // Scroll to warning
                        priceWarning.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        return false;
                    }

                    // Add loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    if (!handleFormSubmit(submitBtn, originalText)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });

        // ==========================================
        // SELECT ALL CHECKBOX FUNCTIONALITY
        // ==========================================

        const selectAllCheckbox = document.getElementById('selectAll');
        const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
        const deleteButton = document.getElementById('delete-button');

        // Function to update delete button state
        function updateDeleteButtonState() {
            const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        // Uncheck "Select All" if any individual checkbox is unchecked
        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        // Initialize button state on page load
        updateDeleteButtonState();

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
        }
    });
</script>
