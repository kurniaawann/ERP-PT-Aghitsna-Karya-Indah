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
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        return true;
    }

    // ==========================================
    // AUTO-GENERATE DOCUMENT NUMBER
    // ==========================================

    function generateDocumentNumber() {
        const now = new Date();
        const date = now.toISOString().slice(0, 10).replace(/-/g, '');
        const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        return `DN-${date}-${random}`;
    }

    // Set document number on page load or when modal opens
    document.addEventListener('DOMContentLoaded', function() {
        const docNumField = document.getElementById('documentNumber');
        if (docNumField) {
            docNumField.value = generateDocumentNumber();
        }
    });

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
    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const printButton = document.getElementById('printDropdownButton');
        const selectedCountText = document.getElementById('selectedCountText');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        const count = checkedCheckboxes.length;

        // Update selected count text
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

        if (printButton) {
            if (count > 0) {
                printButton.disabled = false;
                printButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                printButton.disabled = true;
                printButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Initialize button states
    updateButtonStates();

    // ==========================================
    // SUBMIT DELETE FORM
    // ==========================================

    function submitDeleteForm() {
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checkedCheckboxes.length === 0) {
            alert('Tidak ada data yang dipilih!');
            return;
        }

        // Update confirm button in modal
        const confirmBtn = document.getElementById('confirm-btn-deleteModal');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            confirmBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        const form = document.getElementById('deleteForm');
        form.submit();
    }

    // ==========================================
    // PRINT SELECTED FUNCTION
    // ==========================================

    function printSelected() {
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

        if (checkedCheckboxes.length === 0) {
            alert('Tidak ada data yang dipilih!');
            return;
        }

        // Create a temporary form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('delivery-note.administrasi.export.pdf.selected') }}';
        form.style.display = 'none';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);

        // Add all checked IDs
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });

        // Submit form
        document.body.appendChild(form);
        form.submit();

        // Close dropdown after submit
        const dropdownMenu = document.getElementById('printDropdownMenu');
        if (dropdownMenu) {
            dropdownMenu.classList.add('hidden');
        }
    }

    // ==========================================
    // ADD ITEM ROW
    // ==========================================

    function addItemRow(modalId) {
        const container = document.getElementById(`itemsContainer-${modalId}`);

        // Get current number of items
        const itemRows = container.querySelectorAll('.item-row');
        const newNo = itemRows.length + 1;

        const newRow = document.createElement('div');
        newRow.className =
            'item-row bg-white border-2 border-gray-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';
        newRow.innerHTML = `
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">No</label>
                    <input type="number" name="item_no[]"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="1" min="1" value="${newNo}" required readonly>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Nama Barang <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="item_name[]"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="Masukkan nama barang..." required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Jumlah <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="quantity[]"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="0" min="1" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Satuan</label>
                    <input type="text" name="unit[]"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="pcs" value="pcs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Catatan</label>
                    <input type="text" name="item_notes[]"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="Masukkan catatan...">
                </div>
                <button type="button" onclick="removeItemRow(this)"
                    class="delete-btn w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-trash"></i>
                    <span>Hapus</span>
                </button>
            </div>
        `;

        container.appendChild(newRow);

        // Update delete button visibility
        updateDeleteButtonVisibility(modalId);
    }

    // ==========================================
    // REMOVE ITEM ROW
    // ==========================================

    function removeItemRow(button) {
        const row = button.closest('.item-row');
        if (row) {
            row.remove();
            // Update the modal ID to ensure delete buttons are properly toggled
            const modal = button.closest('[id^="addModal"], [id^="editModal-"]');
            if (modal) {
                updateDeleteButtonVisibility(modal.id);
            }
        }
    }

    // ==========================================
    // UPDATE DELETE BUTTON VISIBILITY
    // ==========================================

    function updateDeleteButtonVisibility(modalId) {
        const container = document.getElementById(`itemsContainer-${modalId}`);
        const itemRows = container.querySelectorAll('.item-row');

        itemRows.forEach((row, index) => {
            const deleteBtn = row.querySelector('.delete-btn');
            // Show delete button only if there are more than 1 item rows
            if (deleteBtn) {
                deleteBtn.style.display = itemRows.length > 1 ? 'flex' : 'none';
            }
        });
    }

    // Initialize delete button visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="itemsContainer-"]').forEach(container => {
            const modalId = container.id.replace('itemsContainer-', '');
            updateDeleteButtonVisibility(modalId);
        });
    });
</script>
