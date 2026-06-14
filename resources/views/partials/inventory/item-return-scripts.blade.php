{{-- Return Scripts --}}
<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // INDIVIDUAL DELETE FUNCTION
    // ==========================================

    function submitDeleteForm() {
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        const form = document.getElementById('deleteForm');
        if (form) {
            form.submit();
        }
    }

    // ==========================================
    // PRINT DROPDOWN FUNCTIONALITY
    // ==========================================

    function initPrintDropdown() {
        const printDropdownButton = document.getElementById('printDropdownButton');
        const printDropdownMenu = document.getElementById('printDropdownMenu');

        if (printDropdownButton && printDropdownMenu) {
            printDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                printDropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                    printDropdownMenu.classList.add('hidden');
                }
            });

            // Prevent dropdown from closing when clicking inside
            printDropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    }

    // ==========================================
    // ITEM RETURN DATA & CONFIG (SEARCHABLE DROPDOWN)
    // ==========================================

    (function() {
        const addReturnTypeSelect = document.getElementById('addReturnType');
        const addItemBtn = document.getElementById('addItemDropdownBtn');
        const addItemMenu = document.getElementById('addItemDropdownMenu');
        const addItemSearch = document.getElementById('addItemSearchInput');
        const addItemList = document.getElementById('addItemDropdownList');
        const addItemLabel = document.getElementById('addItemDropdownLabel');
        
        const addItemIdInput = document.getElementById('addItemId');
        const addStockInIdInput = document.getElementById('addStockInId');
        const addStockOutIdInput = document.getElementById('addStockOutId');

        const addQuantityInput = document.getElementById('addQuantity');
        const addQuantityWarning = document.getElementById('addQuantityWarning');
        const addAvailableStock = document.getElementById('addAvailableStock');
        const addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

        if (!addReturnTypeSelect || !addItemBtn) return;

        let page = 1;
        const limit = 10;
        let loading = false;
        let hasMore = true;
        let searchTimeout = null;
        let currentMaxQuantity = 0;

        function resetItemSelection() {
            addItemIdInput.value = '';
            addStockInIdInput.value = '';
            addStockOutIdInput.value = '';
            addItemLabel.textContent = '-- Pilih Barang --';
            currentMaxQuantity = 0;
            addAvailableStock.textContent = '';
            validateAddQuantity();
        }

        function validateAddQuantity() {
            const qty = parseInt(addQuantityInput.value) || 0;
            if (qty > currentMaxQuantity && currentMaxQuantity > 0) {
                addQuantityWarning.classList.remove('hidden');
                if (addSubmitBtn) addSubmitBtn.disabled = true;
            } else {
                addQuantityWarning.classList.add('hidden');
                if (addSubmitBtn) addSubmitBtn.disabled = false;
            }
        }

        function fetchStockItems(append = false) {
            if (loading || (!hasMore && append)) return;
            loading = true;

            const returnType = addReturnTypeSelect.value;
            const search = addItemSearch.value;

            const params = new URLSearchParams({
                return_type: returnType,
                search: search,
                page: String(page),
                limit: String(limit)
            });

            if (!append) {
                addItemList.innerHTML = '<div class="p-2 text-center text-sm text-text-secondary">Memuat...</div>';
            }

            fetch('{{ route('item-return.stock-dropdown') }}?' + params.toString())
                .then(r => r.json())
                .then(res => {
                    if (!append) addItemList.innerHTML = '';
                    
                    const data = res.data || [];
                    if (data.length === 0 && !append) {
                        addItemList.innerHTML = '<div class="p-2 text-center text-sm text-text-secondary">Tidak ada data</div>';
                    } else {
                        data.forEach(item => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'w-full text-left px-3 py-2 hover:bg-surface-secondary text-sm text-text-primary transition-colors border-b border-border-light last:border-0';
                            btn.innerHTML = `
                                <div class="font-semibold">${item.id_item} - ${item.name_item}</div>
                                <div class="text-xs text-text-secondary">Stok Tersedia: <span class="text-primary font-bold">${item.quantity}</span></div>
                            `;

                            btn.addEventListener('click', () => {
                                addItemIdInput.value = item.id_item;
                                if (returnType === 'masuk') {
                                    addStockInIdInput.value = item.stock_record_id;
                                    addStockOutIdInput.value = '';
                                } else {
                                    addStockOutIdInput.value = item.stock_record_id;
                                    addStockInIdInput.value = '';
                                }
                                
                                addItemLabel.textContent = `${item.id_item} - ${item.name_item}`;
                                currentMaxQuantity = item.quantity;
                                addAvailableStock.textContent = `Stok tersedia: ${currentMaxQuantity}`;
                                validateAddQuantity();
                                addItemMenu.classList.add('hidden');
                            });

                            addItemList.appendChild(btn);
                        });
                    }

                    hasMore = res.hasMore;
                    if (hasMore) page++;
                })
                .finally(() => {
                    loading = false;
                });
        }

        // Return Type Change logic
        addReturnTypeSelect.addEventListener('change', function() {
            const val = this.value;
            if (val) {
                addItemBtn.disabled = false;
                addItemLabel.textContent = '-- Pilih Barang --';
            } else {
                addItemBtn.disabled = true;
                addItemLabel.textContent = '-- Pilih Tipe Return Dulu --';
            }
            resetItemSelection();
            addItemMenu.classList.add('hidden');
        });

        // Toggle Menu
        addItemBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            addItemMenu.classList.toggle('hidden');
            if (!addItemMenu.classList.contains('hidden')) {
                addItemSearch.focus();
                page = 1;
                hasMore = true;
                fetchStockItems(false);
            }
        });

        // Search logic
        addItemSearch.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                page = 1;
                hasMore = true;
                fetchStockItems(false);
            }, 300);
        });

        // Infinite scroll logic
        addItemList.addEventListener('scroll', () => {
            const nearBottom = addItemList.scrollTop + addItemList.clientHeight >= addItemList.scrollHeight - 20;
            if (nearBottom) fetchStockItems(true);
        });

        // Close on outside click
        document.addEventListener('click', () => addItemMenu.classList.add('hidden'));
        addItemMenu.addEventListener('click', (e) => e.stopPropagation());

        // Quantity validation
        addQuantityInput.addEventListener('input', validateAddQuantity);
    })();

    document.addEventListener('DOMContentLoaded', function() {


        // Spinner Loading for Add, Edit, and Delete
        function showSpinner(button) {
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
        }



        // ==========================================
        // INITIALIZE PRINT DROPDOWN
        // ==========================================
        initPrintDropdown();

        // Add Form Setup
        const addModal = document.getElementById('addModal');
        const addFormElement = addModal ? addModal.querySelector('form') : null;
        const addButton = addFormElement ? addFormElement.querySelector('button[type="submit"]') : null;

        if (addFormElement && addButton) {
            addFormElement.addEventListener('submit', function(e) {
                // validation is already handled by the new logic above setting disabled state
                showSpinner(addButton);
            });
        }

        // Edit Form Submission with Real-time Quantity Validation
        document.querySelectorAll('[id^="editModal-"]').forEach(editModal => {
            const editForm = editModal.querySelector('form');
            const editButton = editModal.querySelector('form button[type="submit"]');
            const returnId = editModal.id.replace('editModal-', '');
            const quantityInput = document.getElementById(`editQuantity-${returnId}`);
            const quantityWarning = document.getElementById(`editQuantityWarning-${returnId}`);
            const availableStock = document.getElementById(`editAvailableStock-${returnId}`);

            if (editForm && editButton && quantityInput) {
                const maxQuantity = parseInt(quantityInput.dataset.maxQuantity) || 0;

                // Real-time validation function
                function validateEditQuantity() {
                    const inputQuantity = parseInt(quantityInput.value) || 0;

                    if (inputQuantity > maxQuantity && maxQuantity > 0) {
                        quantityWarning.classList.remove('hidden');
                        editButton.disabled = true;
                        editButton.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        quantityWarning.classList.add('hidden');
                        editButton.disabled = false;
                        editButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }

                // Listen to input changes
                quantityInput.addEventListener('input', validateEditQuantity);

                // Initial validation
                validateEditQuantity();

                // On form submit
                editForm.addEventListener('submit', function(e) {
                    validateEditQuantity();
                    if (editButton.disabled) {
                        e.preventDefault();
                        quantityWarning.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        return false;
                    }
                    showSpinner(editButton);
                });
            }
        });

        // Delete Form Submission
        const deleteForm = document.getElementById('deleteForm');
        const deleteButton = document.getElementById('confirm-btn-deleteModal');
        if (deleteForm && deleteButton) {
            deleteForm.addEventListener('submit', function() {
                showSpinner(deleteButton);
            });
        }

        // Filter by Month, Year, and Type
        const monthFilter = document.getElementById('month-select');
        const yearFilter = document.getElementById('year-select');
        const typeFilter = document.getElementById('return_type');

        [monthFilter, yearFilter, typeFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', function() {
                    // Get the form parent - traverse up DOM tree
                    let form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                });
            }
        });

        // Bulk Delete Handling
        const selectAllCheckbox = document.getElementById('selectAll');
        const itemCheckboxes = document.querySelectorAll('input[name="selected_returns[]"]');
        const bulkDeleteButton = document.getElementById('delete-button');

        function updateBulkDeleteButtonState() {
            const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
            if (bulkDeleteButton) {
                bulkDeleteButton.disabled = !anyChecked;
                bulkDeleteButton.classList.toggle('opacity-50', !anyChecked);
                bulkDeleteButton.classList.toggle('cursor-not-allowed', !anyChecked);
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(cb => cb.checked = this.checked);
                updateBulkDeleteButtonState();
            });
        }

        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkDeleteButtonState();
                // Update selectAll checkbox state
                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
            });
        });

        // Initial button state
        updateBulkDeleteButtonState();
    });
</script>
