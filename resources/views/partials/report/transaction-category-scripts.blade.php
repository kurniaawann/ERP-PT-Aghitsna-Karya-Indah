<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // BULK DELETE - CHECK USED CATEGORIES
    // ==========================================

    function checkAndDelete() {
        const checkboxes = document.querySelectorAll('.category-checkbox:checked');

        // Check if any selected category is being used
        const usedCategories = [];
        checkboxes.forEach(cb => {
            if (cb.dataset.isUsed === 'true') {
                usedCategories.push(cb.dataset.categoryName);
            }
        });

        if (usedCategories.length > 0) {
            // Show warning modal
            showWarningModal(usedCategories);
        } else {
            // Show delete confirmation modal
            openModal('deleteModal');
        }
    }

    function showWarningModal(usedCategories) {
        const modal = document.getElementById('warningUsedModal');
        const list = document.getElementById('usedCategoriesList');
        const modalContent = modal.querySelector('.bg-surface-base');

        // Clear previous list
        list.innerHTML = '';

        // Add used categories to list
        usedCategories.forEach(categoryName => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${categoryName}</strong>`;
            li.classList.add('font-medium');
            list.appendChild(li);
        });

        // Show modal with animation
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeWarningModal() {
        const modal = document.getElementById('warningUsedModal');
        const modalContent = modal.querySelector('.bg-surface-base');

        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

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

    // ==========================================
    // TOGGLE STATUS FUNCTION
    // ==========================================

    function toggleStatus(categoryId) {
        // Create a temporary form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/transaction-category/${categoryId}/toggle-status`;

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        // Add method spoofing for PATCH
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        form.appendChild(methodInput);

        // Append to body and submit
        document.body.appendChild(form);
        form.submit();
    }

    // ==========================================
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // CODE VALIDATION FOR ADD MODAL
        // ==========================================

        const existingCodes = @json(array_values($existingCodes ?? []));
        const addCodeInput = document.getElementById('add-code');
        const addCodeWarning = document.getElementById('add-code-warning');
        const addCodeWarningText = document.getElementById('add-code-warning-text');
        const addSubmitBtn = document.getElementById('submit-btn-addModal');

        function validateAddCode() {
            if (!addCodeInput) return true;

            const code = addCodeInput.value.trim().toUpperCase();

            // Cek apakah kode sudah digunakan
            if (existingCodes.includes(code)) {
                addCodeWarning.classList.remove('hidden');
                addCodeInput.classList.add('border-red-500', 'border-2');
                addCodeWarningText.textContent =
                    `Kode "${code}" sudah digunakan! Silakan gunakan kode yang berbeda.`;

                // Disable submit button
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = true;
                    addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else {
                addCodeWarning.classList.add('hidden');
                addCodeInput.classList.remove('border-red-500', 'border-2');
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = false;
                    addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return true;
            }
        }

        if (addCodeInput) {
            addCodeInput.addEventListener('input', validateAddCode);
            addCodeInput.addEventListener('blur', validateAddCode);
        }

        // ==========================================
        // CODE VALIDATION FOR EDIT MODALS
        // ==========================================

        const existingCodesWithId = @json($existingCodes ?? []);
        const editCodeInputs = document.querySelectorAll('[id^="edit-code-"]');

        editCodeInputs.forEach(function(editCodeInput) {
            const categoryId = editCodeInput.getAttribute('data-category-id');
            const editCodeWarning = document.getElementById('edit-code-warning-' + categoryId);
            const editCodeWarningText = document.getElementById('edit-code-warning-text-' + categoryId);
            const editSubmitBtn = document.getElementById('submit-btn-editModal-' + categoryId);

            function validateEditCode() {
                const code = editCodeInput.value.trim().toUpperCase();

                // Cek apakah kode sudah digunakan oleh kategori lain (exclude kategori ini sendiri)
                let codeExists = false;
                for (let id in existingCodesWithId) {
                    if (id != categoryId && existingCodesWithId[id] === code) {
                        codeExists = true;
                        break;
                    }
                }

                if (codeExists) {
                    editCodeWarning.classList.remove('hidden');
                    editCodeInput.classList.add('border-red-500', 'border-2');
                    editCodeWarningText.textContent =
                        `Kode "${code}" sudah digunakan! Silakan gunakan kode yang berbeda.`;

                    // Disable submit button
                    if (editSubmitBtn) {
                        editSubmitBtn.disabled = true;
                        editSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    editCodeWarning.classList.add('hidden');
                    editCodeInput.classList.remove('border-red-500', 'border-2');
                    if (editSubmitBtn) {
                        editSubmitBtn.disabled = false;
                        editSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    return true;
                }
            }

            editCodeInput.addEventListener('input', validateEditCode);
            editCodeInput.addEventListener('blur', validateEditCode);
        });

        // ==========================================
        // FORM SUBMIT HANDLING - ADD MODAL
        // ==========================================

        const addForm = document.querySelector('#addModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                // Validasi code sebelum submit
                if (addCodeInput && !validateAddCode()) {
                    e.preventDefault();
                    addCodeWarning.classList.remove('hidden');
                    addCodeInput.focus();
                    return false;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                if (!handleFormSubmit(submitBtn, originalText)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // ==========================================
        // FORM SUBMIT HANDLING - EDIT MODALS
        // ==========================================

        const editForms = document.querySelectorAll('[id^="editModal-"] form');
        editForms.forEach(function(editForm) {
            editForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                if (!handleFormSubmit(submitBtn, originalText)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // ==========================================
        // SELECT ALL CHECKBOX FUNCTIONALITY
        // ==========================================

        const selectAllCheckbox = document.getElementById('selectAll');
        const categoryCheckboxes = document.querySelectorAll('input[name="selected_categories[]"]');
        const deleteButton = document.getElementById('delete-button');

        // Function to update delete button state
        function updateDeleteButtonState() {
            const anyChecked = Array.from(categoryCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        // Uncheck "Select All" if any individual checkbox is unchecked
        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(categoryCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        // Initialize button state on page load
        updateDeleteButtonState();
    });
</script>
