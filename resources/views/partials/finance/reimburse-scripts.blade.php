<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

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
        const approvalButton = document.getElementById('approval-dropdown-button');
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

        // Approval dropdown button (Super Admin only)
        if (approvalButton) {
            if (checkedCheckboxes.length > 0) {
                approvalButton.disabled = false;
                approvalButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                approvalButton.disabled = true;
                approvalButton.classList.add('opacity-50', 'cursor-not-allowed');
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

    // ==========================================
    // APPROVAL DROPDOWN TOGGLE
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        const approvalButton = document.getElementById('approval-dropdown-button');
        const approvalMenu = document.getElementById('approval-dropdown-menu');

        if (approvalButton && approvalMenu) {
            approvalButton.addEventListener('click', function(e) {
                e.stopPropagation();
                if (!this.disabled) {
                    approvalMenu.classList.toggle('hidden');
                }
            });

            document.addEventListener('click', function(e) {
                if (!approvalButton.contains(e.target) && !approvalMenu.contains(e.target)) {
                    approvalMenu.classList.add('hidden');
                }
            });

            approvalMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>
