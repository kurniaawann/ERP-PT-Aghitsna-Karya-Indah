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
    // TOGGLE ACTIVE STATUS FUNCTION
    // ==========================================

    function toggleActive(accountId) {
        // Create a temporary form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/payment-accounts/${accountId}/toggle`;

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        // Append to body and submit
        document.body.appendChild(form);
        form.submit();
    }

    // ==========================================
    // BULK DELETE FUNCTION
    // ==========================================

    function submitDeleteForm() {
        const checkboxes = document.querySelectorAll('.account-checkbox:checked');

        if (checkboxes.length === 0) {
            alert('Pilih minimal satu rekening untuk dihapus');
            return;
        }

        // Show loading on delete button
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
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // SELECT ALL CHECKBOX
        // ==========================================

        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.account-checkbox');
        const deleteButton = document.getElementById('delete-button');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButton();
            });
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                const someChecked = Array.from(checkboxes).some(cb => cb.checked);

                if (selectAll) {
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = !allChecked && someChecked;
                }

                updateDeleteButton();
            });
        });

        function updateDeleteButton() {
            const checkedCount = document.querySelectorAll('.account-checkbox:checked').length;
            if (deleteButton) {
                deleteButton.disabled = checkedCount === 0;
            }
        }

        // ==========================================
        // ADD/EDIT FORM SUBMIT HANDLERS
        // ==========================================

        // Handle Add Modal Submit
        const addForm = document.querySelector('#addModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Handle Edit Modal Submits
        document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // ==========================================
        // AUTO CLOSE ALERTS
        // ==========================================

        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    });
</script>
