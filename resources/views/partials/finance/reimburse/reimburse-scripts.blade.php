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
    // SELECT ALL CHECKBOX
    // ==========================================

    // Select All Checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateButtonStates();
            updateSelectedInfo();
        });
    }

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length && checkboxes
                    .length > 0;
            }
            updateButtonStates();
            updateSelectedInfo();
        });
    });

    // ==========================================
    // UPDATE BUTTON STATES
    // ==========================================

    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const approveButton = document.getElementById('approve-button');
        const rejectButton = document.getElementById('reject-button');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

        // Delete button
        if (deleteButton) {
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

        // Approve/Reject buttons (Super Admin only)
        if (approveButton && rejectButton) {
            if (checkedCheckboxes.length > 0) {
                approveButton.disabled = false;
                approveButton.classList.remove('opacity-50', 'cursor-not-allowed');
                approveButton.classList.add('hover:bg-green-700');

                rejectButton.disabled = false;
                rejectButton.classList.remove('opacity-50', 'cursor-not-allowed');
                rejectButton.classList.add('hover:bg-red-700');
            } else {
                approveButton.disabled = true;
                approveButton.classList.add('opacity-50', 'cursor-not-allowed');
                approveButton.classList.remove('hover:bg-green-700');

                rejectButton.disabled = true;
                rejectButton.classList.add('opacity-50', 'cursor-not-allowed');
                rejectButton.classList.remove('hover:bg-red-700');
            }
        }
    }

    // ==========================================
    // UPDATE SELECTED INFO (Super Admin)
    // ==========================================

    function updateSelectedInfo() {
        const selectedInfo = document.getElementById('selected-info');
        const selectedCount = document.getElementById('selected-count');
        const selectedTotal = document.getElementById('selected-total');
        const approveTotal = document.getElementById('approve-total');

        if (!selectedInfo) return;

        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        const count = checkedCheckboxes.length;

        if (count > 0) {
            // Calculate total amount
            let total = 0;
            checkedCheckboxes.forEach(checkbox => {
                const amount = parseInt(checkbox.getAttribute('data-amount')) || 0;
                total += amount;
            });

            // Show info
            selectedInfo.classList.remove('hidden');
            selectedCount.textContent = count;
            selectedTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');

            // Update approve modal total
            const approveTotalModal = document.getElementById('approve-total-modal');
            if (approveTotalModal) {
                approveTotalModal.textContent = 'Rp ' + total.toLocaleString('id-ID');
            }

            // Update reject modal total
            const rejectTotalModal = document.getElementById('reject-total-modal');
            if (rejectTotalModal) {
                rejectTotalModal.textContent = 'Rp ' + total.toLocaleString('id-ID');
            }

            // Update count text
            const approveCountText = document.getElementById('approve-count-text');
            if (approveCountText) {
                approveCountText.textContent = count;
            }

            const rejectCountText = document.getElementById('reject-count-text');
            if (rejectCountText) {
                rejectCountText.textContent = count;
            }
        } else {
            // Hide info
            selectedInfo.classList.add('hidden');
        }
    }

    // ==========================================
    // SUBMIT FORMS
    // ==========================================

    // Submit Delete Form
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
    // EXPORT FUNCTIONS
    // ==========================================

    function exportData(type) {
        const search = new URLSearchParams(window.location.search).get('search') || '';
        const status = new URLSearchParams(window.location.search).get('status') || '';

        let url = '';
        if (type === 'pdf') {
            url = `{{ route('reimburse.export.pdf') }}?search=${search}&status=${status}`;
        } else if (type === 'excel') {
            url = `{{ route('reimburse.export.excel') }}?search=${search}&status=${status}`;
        }

        if (url) {
            window.location.href = url;
        }
    }

    // ==========================================
    // ADD/EDIT FORM SUBMIT HANDLERS
    // ==========================================

    // Add Modal Submit
    const addModalForm = document.querySelector('#addModal form');
    if (addModalForm) {
        addModalForm.addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('#submit-btn-addModal');
            if (!handleFormSubmit(submitBtn, 'Simpan')) {
                e.preventDefault();
            }
        });
    }

    // Edit Modal Submit (for all edit modals)
    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const modalId = modal.id;
                const submitBtn = document.querySelector(`#submit-btn-${modalId}`);
                if (!handleFormSubmit(submitBtn, 'Update')) {
                    e.preventDefault();
                }
            });
        }
    });

    // Approve Modal Submit
    const approveModalForm = document.querySelector('#approveModal form');
    if (approveModalForm) {
        approveModalForm.addEventListener('submit', function(e) {
            // Add hidden inputs before submit
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            const hiddenInputsContainer = document.getElementById('approve-hidden-inputs');

            if (hiddenInputsContainer) {
                // Clear previous inputs
                hiddenInputsContainer.innerHTML = '';

                // Add hidden inputs for selected reimburses
                checkedCheckboxes.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = checkbox.value;
                    hiddenInputsContainer.appendChild(input);
                });
            }

            const submitBtn = document.querySelector('#submit-btn-approveModal');
            if (!handleFormSubmit(submitBtn, 'Setujui')) {
                e.preventDefault();
            }
        });
    }

    // Reject Modal Submit
    const rejectModalForm = document.querySelector('#rejectModal form');
    if (rejectModalForm) {
        rejectModalForm.addEventListener('submit', function(e) {
            // Add hidden inputs before submit
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
            const hiddenInputsContainer = document.getElementById('reject-hidden-inputs');

            if (hiddenInputsContainer) {
                // Clear previous inputs
                hiddenInputsContainer.innerHTML = '';

                // Add hidden inputs for selected reimburses
                checkedCheckboxes.forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = checkbox.value;
                    hiddenInputsContainer.appendChild(input);
                });
            }

            const submitBtn = document.querySelector('#submit-btn-rejectModal');
            if (!handleFormSubmit(submitBtn, 'Tolak')) {
                e.preventDefault();
            }
        });
    }

    // ==========================================
    // INITIALIZE ON PAGE LOAD
    // ==========================================

    updateButtonStates();
    updateSelectedInfo();
</script>
