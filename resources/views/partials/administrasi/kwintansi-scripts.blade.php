<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')

    // ==========================================
    // SELECT ALL CHECKBOX
    // ==========================================

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    // Update Delete Button and Print Button State
    deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
    deleteButton.classList.add('hover:bg-btn-delete-hover');
    }
    else {
        deleteButton.disabled = true;
        deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        return sharedPrintSelected('{{ route('kwintansi.export.pdf.selected') }}', 'input[name="ids[]"]:checked',
            'Silakan pilih data terlebih dahulu!');
        updateButtonStates();

        // ==========================================
        // PRINT SELECTED HANDLER
        // ==========================================

        function printSelected(btn) {
            return sharedPrintSelected('{{ route('kwintansi.export.pdf.selected') }}', btn);
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
</script>
