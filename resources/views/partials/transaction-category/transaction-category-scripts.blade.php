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

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
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
            });
        });

        // ==========================================
        // DELETE MODAL VALIDATION
        // ==========================================

        const deleteButton = document.querySelector('button[onclick*="deleteModal"]');
        if (deleteButton) {
            deleteButton.addEventListener('click', function(e) {
                const selectedCheckboxes = document.querySelectorAll(
                    'input[name="selected_categories[]"]:checked');
                if (selectedCheckboxes.length === 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Pilih minimal satu kategori untuk dihapus!');
                    return false;
                }
            });
        }
    });
</script>
