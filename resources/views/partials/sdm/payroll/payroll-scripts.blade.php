<script>
    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]:not(:disabled)').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            const checkedCheckboxes = document.querySelectorAll(
                'input[name="ids[]"]:not(:disabled):checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    // Update Button States (Delete & Bulk Pay)
    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const bulkPayButton = document.getElementById('bulk-pay-button');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled):checked');

        if (checkedCheckboxes.length > 0) {
            // Enable Delete Button
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');

            // Enable Bulk Pay Button
            bulkPayButton.disabled = false;
            bulkPayButton.classList.remove('opacity-50', 'cursor-not-allowed');
            bulkPayButton.classList.add('hover:bg-blue-700');
        } else {
            // Disable Delete Button
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');

            // Disable Bulk Pay Button
            bulkPayButton.disabled = true;
            bulkPayButton.classList.add('opacity-50', 'cursor-not-allowed');
            bulkPayButton.classList.remove('hover:bg-blue-700');
        }
    }

    // Submit Delete Form
    function submitDeleteForm() {
        const checkedCheckboxes = document.querySelectorAll('.payroll-checkbox:checked');
        const deleteForm = document.getElementById('deleteForm');

        if (checkedCheckboxes.length === 0) {
            return; // Don't submit if nothing is selected
        }

        // Remove previous inputs
        const existingIds = deleteForm.querySelectorAll('input[name="ids[]"]');
        existingIds.forEach(input => input.remove());

        // Add checked IDs to delete form
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            deleteForm.appendChild(input);
        });

        // Submit form
        deleteForm.submit();
    }

    // Submit Bulk Pay Form
    function submitBulkPayForm() {
        const checkedCheckboxes = document.querySelectorAll('.payroll-checkbox:checked');
        const bulkPayForm = document.getElementById('bulkPayForm');

        if (checkedCheckboxes.length === 0) {
            return; // Don't submit if nothing is selected
        }

        // Remove previous dynamic inputs
        const existingIds = bulkPayForm.querySelectorAll('input[name="ids[]"]');
        existingIds.forEach(input => input.remove());
        const existingDate = bulkPayForm.querySelector('input[name="payment_date"]');
        if (existingDate) existingDate.remove();

        // Add checked IDs to bulk pay form
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            bulkPayForm.appendChild(input);
        });

        // Add payment date (today)
        const dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'payment_date';
        dateInput.value = new Date().toISOString().split('T')[0];
        bulkPayForm.appendChild(dateInput);

        // Submit form
        bulkPayForm.submit();
    }

    // Initialize button states on page load
    updateButtonStates();
</script>
