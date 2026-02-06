<script>
    // Toggle employee select based on kasbon type
    function toggleEmployeeSelect(prefix) {
        const kasbonTypeSelect = document.getElementById(prefix + '_kasbon_type');
        const employeeField = document.getElementById(prefix + '_employee_field');
        const employeeSelect = document.getElementById(prefix + '_employee_id');
        const divisionField = document.getElementById(prefix + '_division_field');
        const divisionSelect = document.getElementById(prefix + '_division');

        if (kasbonTypeSelect && employeeField && divisionField) {
            if (kasbonTypeSelect.value === 'team') {
                // Hide employee field, show division field
                employeeField.style.display = 'none';
                divisionField.style.display = 'block';

                if (employeeSelect) {
                    employeeSelect.removeAttribute('required');
                    employeeSelect.value = '';
                }
                if (divisionSelect) {
                    divisionSelect.setAttribute('required', 'required');
                }
            } else if (kasbonTypeSelect.value === 'personal') {
                // Show employee field, hide division field
                employeeField.style.display = 'block';
                divisionField.style.display = 'none';

                if (employeeSelect) {
                    employeeSelect.setAttribute('required', 'required');
                }
                if (divisionSelect) {
                    divisionSelect.removeAttribute('required');
                    divisionSelect.value = '';
                }
            } else {
                // No selection, hide both
                employeeField.style.display = 'none';
                divisionField.style.display = 'none';
            }
        }
    }

    // Handle bulk delete
    function submitDeleteForm() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked:not(:disabled)');

        if (checkedBoxes.length === 0) {
            alert('Pilih minimal 1 data untuk dihapus');
            closeModal('deleteModal');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('kasbon.destroy', ':id') }}'.replace(':id', 'bulk');

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        form.innerHTML = `
        <input type="hidden" name="_token" value="${csrfToken}">
        <input type="hidden" name="_method" value="DELETE">
    `;

        // Gunakan kasbon pertama yang dipilih sebagai ID untuk route
        const firstCheckedId = checkedBoxes[0].value;
        form.action = '{{ route('kasbon.destroy', ':id') }}'.replace(':id', firstCheckedId);

        document.body.appendChild(form);
        form.submit();
    }

    // Select All Checkbox Handler
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const deleteButton = document.querySelector('[onclick*="deleteModal"]');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => {
                    if (!checkbox.disabled) {
                        checkbox.checked = this.checked;
                    }
                });
                updateDeleteButtonState();
            });
        }

        if (rowCheckboxes) {
            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButtonState();

                    // Update select-all state
                    if (selectAll) {
                        const allChecked = Array.from(rowCheckboxes)
                            .filter(cb => !cb.disabled)
                            .every(cb => cb.checked);
                        selectAll.checked = allChecked;
                    }
                });
            });
        }

        function updateDeleteButtonState() {
            const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked && !cb.disabled);

            if (deleteButton) {
                if (anyChecked) {
                    deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    deleteButton.disabled = false;
                } else {
                    deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
                    deleteButton.disabled = true;
                }
            }
        }

        // Initial state
        updateDeleteButtonState();

        // Initialize employee field visibility for add modal
        toggleEmployeeSelect('add');
    });
</script>
