<script>
    // ==========================================
    // CURRENCY FORMATTING UTILITIES
    // ==========================================

    @include('partials.shared.currency-utils-script')

    // ==========================================
    // SHARED DELETE & PRINT SCRIPTS
    // ==========================================

    @include('partials.shared.delete-form-script')
    @include('partials.shared.print-selected-script')

    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // SELECT ALL CHECKBOX
    // ==========================================

    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

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

    updateButtonStates();

    // ==========================================
    // ITEM ROW FUNCTIONS
    // ==========================================

    function updateDeleteButtons(modalId) {
        const container = document.getElementById('itemsContainer-' + modalId);
        const items = container.querySelectorAll('.item-row');
        const deleteButtons = container.querySelectorAll('.delete-btn');

        deleteButtons.forEach(btn => {
            btn.style.display = items.length > 1 ? 'flex' : 'none';
        });
    }

    function calculateItemTotal(row) {
        const qty = parseInt(row.querySelector('input[name="item_banyaknya[]"]')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('input[name="item_harga_satuan[]"]')?.value);
        const total = qty * harga;
        const totalEl = row.querySelector('.item-total');
        if (totalEl) {
            totalEl.textContent = total ? new Intl.NumberFormat('id-ID').format(total) : '0';
        }
        return total;
    }

    function calculateGrandTotal(modalId) {
        const container = document.getElementById('itemsContainer-' + modalId);
        if (!container) return 0;
        const rows = container.querySelectorAll('.item-row');
        let grandTotal = 0;
        rows.forEach(row => {
            const qty = parseInt(row.querySelector('input[name="item_banyaknya[]"]')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('input[name="item_harga_satuan[]"]')?.value);
            grandTotal += qty * harga;
        });
        const grandTotalEl = document.getElementById('grandTotal-' + modalId);
        if (grandTotalEl) {
            grandTotalEl.textContent = new Intl.NumberFormat('id-ID').format(grandTotal);
        }
        return grandTotal;
    }

    function attachItemListeners(row, modalId) {
        const qtyInput = row.querySelector('input[name="item_banyaknya[]"]');
        const hargaInput = row.querySelector('input[name="item_harga_satuan[]"]');

        function recalc() {
            calculateItemTotal(row);
            calculateGrandTotal(modalId);
        }

        if (qtyInput) qtyInput.addEventListener('input', recalc);
        if (hargaInput) {
            hargaInput.addEventListener('keyup', recalc);
            hargaInput.addEventListener('blur', recalc);
        }
    }

    function addItemRow(modalId) {
        const container = document.getElementById('itemsContainer-' + modalId);
        const newRow = document.createElement('div');
        newRow.className =
            'item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';
        newRow.innerHTML = `
            <div class="space-y-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-text-label mb-1.5">Qty <span class="text-error">*</span></label>
                        <input type="number" name="item_banyaknya[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="0" min="1" required>
                    </div>
                    <div class="col-span-10">
                        <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span class="text-error">*</span></label>
                        <input type="text" name="item_nama_barang[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="Masukkan nama barang..." required>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4">
                        <label class="block text-xs font-semibold text-text-label mb-1.5">Harga Satuan <span class="text-error">*</span></label>
                        <input type="text" name="item_harga_satuan[]" class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right text-text-input price-input focus:ring-2 focus:ring-primary focus:border-primary transition-all" placeholder="0" required oninput="formatCurrencyInput(this)">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah</label>
                        <div class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right bg-surface-secondary text-text-input item-total">0</div>
                    </div>
                    <div class="col-span-5 flex items-end">
                        <button type="button" onclick="removeItemRow(this)" class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-trash"></i>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(newRow);

        attachItemListeners(newRow, modalId);
        updateDeleteButtons(modalId);
    }

    function removeItemRow(button) {
        const itemRow = button.closest('.item-row');
        const container = itemRow.parentElement;
        const modalId = container.id.replace('itemsContainer-', '');

        itemRow.remove();
        calculateGrandTotal(modalId);
        updateDeleteButtons(modalId);
    }

    // ==========================================
    // PRINT SELECTED
    // ==========================================

    function printSelected(btn) {
        return sharedPrintSelected('{{ route('nota.administrasi.export.pdf.selected') }}', btn);
    }

    // ==========================================
    // INITIALIZATION
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize delete buttons for add modal
        updateDeleteButtons('addModal');
        calculateGrandTotal('addModal');

        // Initialize delete buttons and totals for edit modals
        document.querySelectorAll('[id^="itemsContainer-editModal-"]').forEach(container => {
            const modalId = container.id.replace('itemsContainer-', '');
            updateDeleteButtons(modalId);
            calculateGrandTotal(modalId);
        });

        // Initialize currency formatting on all price inputs (optional fields)
        document.querySelectorAll('.price-input').forEach(input => {
            if (!input.hasAttribute('oninput')) {
                input.addEventListener('input', function() {
                    formatCurrencyInput(this);
                });
            }
        });

        // Attach calculation listeners to existing rows
        document.querySelectorAll('[id^="itemsContainer-"]').forEach(container => {
            const modalId = container.id.replace('itemsContainer-', '');
            container.querySelectorAll('.item-row').forEach(row => {
                attachItemListeners(row, modalId);
            });
        });

        // Add modal form submission with loading indicator
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

        // Edit modal forms submission with loading indicator
        document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (!handleFormSubmit(submitBtn, originalText, 'Memperbarui...')) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Delete form - prevent double submission
        const deleteForm = document.getElementById('deleteForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                if (formSubmitState.isSubmitting) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Reset submitting state when modal backdrop is clicked
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                formSubmitState.isSubmitting = false;
            }
        });
    });
</script>
