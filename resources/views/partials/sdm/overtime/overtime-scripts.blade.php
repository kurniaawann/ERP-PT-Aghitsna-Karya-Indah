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

    // Calculate Overtime Total in Add Modal
    const addHoursInput = document.getElementById('add-overtime-hours');
    const addRateInput = document.getElementById('add-overtime-rate');
    const addTotalInput = document.getElementById('add-overtime-total');

    function calculateAddOvertimeTotal() {
        const hours = parseFloat(addHoursInput.value) || 0;
        const rate = parseInt(addRateInput.value) || 0;
        const total = hours * rate;
        addTotalInput.value = 'Rp ' + total.toLocaleString('id-ID');
    }

    if (addHoursInput && addRateInput) {
        addHoursInput.addEventListener('input', calculateAddOvertimeTotal);
        addRateInput.addEventListener('input', calculateAddOvertimeTotal);
    }

    // Calculate Overtime Total in Edit Modals
    document.querySelectorAll('[id^="edit-overtime-hours-"]').forEach(hoursInput => {
        const id = hoursInput.id.replace('edit-overtime-hours-', '');
        const rateInput = document.getElementById('edit-overtime-rate-' + id);
        const totalInput = document.getElementById('edit-overtime-total-' + id);

        function calculateEditOvertimeTotal() {
            const hours = parseFloat(hoursInput.value) || 0;
            const rate = parseInt(rateInput.value) || 0;
            const total = hours * rate;
            totalInput.value = 'Rp ' + total.toLocaleString('id-ID');
        }

        hoursInput.addEventListener('input', calculateEditOvertimeTotal);
        rateInput.addEventListener('input', calculateEditOvertimeTotal);
    });

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
