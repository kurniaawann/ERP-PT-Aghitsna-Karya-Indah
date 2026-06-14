<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')
    @include('partials.shared.select-all-script')

    // ==========================================
    // UPDATE BUTTON STATES
    // ==========================================

    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const printButton = document.getElementById('printDropdownButton');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        const count = checkedCheckboxes.length;

        // Update delete button
        if (count > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
        }

        // Update print button
        if (printButton) {
            if (count > 0) {
                printButton.disabled = false;
                printButton.classList.remove('opacity-50', 'cursor-not-allowed');
                printButton.classList.add('hover:bg-primary-hover');
            } else {
                printButton.disabled = true;
                printButton.classList.add('opacity-50', 'cursor-not-allowed');
                printButton.classList.remove('hover:bg-primary-hover');
            }
        }
    }

    initSelectAll('ids[]', 'delete-button', updateButtonStates);

    // ==========================================
    // PRINT SELECTED HANDLER
    // ==========================================

    function printSelected() {
        return sharedPrintSelected('{{ route('kwintansi.export.pdf.selected') }}', 'input[name="ids[]"]:checked', 'Silakan pilih data terlebih dahulu!');
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
