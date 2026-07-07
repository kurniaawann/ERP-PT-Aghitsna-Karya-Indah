<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    @include('partials.shared.currency-utils-script')

    // Shared helper is loaded from resources/js/shared/form-submit.js

    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // SELECT ALL CHECKBOX
        // ==========================================

        const selectAllEl = document.getElementById('selectAll');
        if (selectAllEl) {
            selectAllEl.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="ids[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateButtonStates();
            });
        }

        document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selectAll = document.getElementById('selectAll');
                const checkboxes = document.querySelectorAll('input[name="ids[]"]');
                const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

                if (selectAll) selectAll.checked = checkboxes.length === checkedCheckboxes.length;
                updateButtonStates();
            });
        });

        updateButtonStates();

        // ==========================================
        // ADD/EDIT FORM SUBMIT HANDLERS
        // ==========================================

        const addForm = document.querySelector('#addModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (!handleFormSubmit(submitBtn, originalText, 'Update...')) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // ==========================================
        // RESET isSubmitting FLAG ON PAGE SHOW
        // ==========================================

        window.addEventListener('pageshow', function() {
            resetFormSubmitState();
        });
    });

    // ==========================================
    // UPDATE BUTTON STATES
    // ==========================================

    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const printSelectedItem = document.getElementById('printSelectedItem');
        const selectedCountText = document.getElementById('selectedCountText');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        const count = checkedCheckboxes.length;

        if (selectedCountText) {
            selectedCountText.textContent = count;
        }

        if (deleteButton) {
            if (count > 0) {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        if (printSelectedItem) {
            if (count > 0) {
                printSelectedItem.classList.remove('hidden');
            } else {
                printSelectedItem.classList.add('hidden');
            }
        }
    }

    // ==========================================
    // PRINT SELECTED FUNCTION
    // ==========================================

    function printSelected(btn) {
        return sharedPrintSelected('{{ route('kwintansi.export.pdf.selected') }}', btn);
    }
</script>
