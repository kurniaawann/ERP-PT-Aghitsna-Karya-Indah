<script>
    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButtonState();
    });

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateDeleteButtonState();
        });
    });

    // Select All Employees in Add Modal
    const selectAllEmployees = document.getElementById('selectAllEmployees');
    if (selectAllEmployees) {
        selectAllEmployees.addEventListener('change', function() {
            const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
            employeeCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update Select All state when individual checkbox changes
        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
                const checkedEmployees = document.querySelectorAll('.employee-checkbox:checked');
                selectAllEmployees.checked = employeeCheckboxes.length === checkedEmployees.length;
            });
        });
    }

    // Update Delete Button State
    function updateDeleteButtonState() {
        const deleteButton = document.getElementById('delete-button');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

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

    // Submit Delete Form
    function submitDeleteForm() {
        document.getElementById('deleteForm').submit();
    }

    // Initialize delete button state on page load
    updateDeleteButtonState();
</script>
