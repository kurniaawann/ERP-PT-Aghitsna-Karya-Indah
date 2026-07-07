<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')

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
    const selectAllEl = document.getElementById('selectAll');
    if (selectAllEl) {
        selectAllEl.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                if (!checkbox.disabled) checkbox.checked = this.checked;
            });
            updateButtonStates();
        });
    }

    // Individual Checkbox
    document.querySelectorAll('.row-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = Array.from(document.querySelectorAll('.row-checkbox')).filter(cb => !cb
                .disabled);
            const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');

            if (selectAll) selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    // Update Delete Button and Export Selected State
    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const printSelectedItem = document.getElementById('printSelectedItem');
        const selectedCountText = document.getElementById('selectedCountText');
        const checkedCheckboxes = Array.from(document.querySelectorAll('.row-checkbox:checked')).filter(cb => !cb
            .disabled);
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

        if (printSelectedItem) {
            if (count > 0) {
                printSelectedItem.classList.remove('hidden');
            } else {
                printSelectedItem.classList.add('hidden');
            }
        }
    }

    // Initialize button states
    updateButtonStates();

    // ==========================================
    // SUBMIT DELETE FORM
    // ==========================================

    function submitDeleteForm() {
        const checkedCheckboxes = Array.from(document.querySelectorAll('.row-checkbox:checked')).filter(cb => !cb
            .disabled);
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

    function printSelected(btn) {
        return sharedPrintSelected('{{ route('delivery-note.administrasi.export.pdf.selected') }}', btn,
            '.row-checkbox:checked:not([disabled])', 'Tidak ada data yang dipilih!');
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
                'item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';
            newRow.innerHTML = `
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">No</label>
                    <input type="number" name="item_no[]"
                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="1" min="1" value="${newNo}" required readonly>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span
                            class="text-error">*</span></label>
                    <input type="text" name="item_name[]"
                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="Masukkan nama barang..." required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah <span
                            class="text-error">*</span></label>
                    <input type="number" name="quantity[]"
                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="0" min="1" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Satuan</label>
                    <input type="text" name="unit[]"
                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="pcs" value="pcs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Catatan</label>
                    <input type="text" name="item_notes[]"
                        class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="Masukkan catatan...">
                </div>
                <button type="button" onclick="removeItemRow(this)"
                    class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
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
