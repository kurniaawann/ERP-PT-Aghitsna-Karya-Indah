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
    // ITEM RETURN DATA & CONFIG
    // ==========================================

    // Data dari server (Stock Ins dan Stock Outs)
    const stockInsData = @json($stockIns);
    const stockOutsData = @json($stockOuts);
    const itemsData = @json($items);

    // Fungsi untuk handle dynamic item selection berdasarkan return type
    function handleReturnTypeChange(modalPrefix) {
        const returnTypeSelect = document.getElementById(modalPrefix + 'ReturnType');
        const itemSelect = document.getElementById(modalPrefix + 'ItemSelect');
        const stockInIdInput = document.getElementById(modalPrefix + 'StockInId');
        const stockOutIdInput = document.getElementById(modalPrefix + 'StockOutId');

        if (!returnTypeSelect || !itemSelect) return;

        const returnType = returnTypeSelect.value;
        itemSelect.innerHTML = '<option value="">-- Pilih Barang --</option>';
        stockInIdInput.value = '';
        stockOutIdInput.value = '';

        if (returnType === 'masuk') {
            // Populate barang dari Stock In
            const uniqueItems = {};
            stockInsData.forEach(stockIn => {
                if (!uniqueItems[stockIn.id_item]) {
                    uniqueItems[stockIn.id_item] = {
                        id_item: stockIn.id_item,
                        name: itemsData.find(i => i.id_item === stockIn.id_item)?.name_item || 'Item',
                        id_stock_in: stockIn.id_stock_in,
                        quantity: stockIn.quantity
                    };
                }
            });

            Object.values(uniqueItems).forEach(item => {
                const option = document.createElement('option');
                option.value = item.id_item;
                option.textContent = `${item.id_item} - ${item.name} (Stok: ${item.quantity})`;
                option.dataset.stockInId = item.id_stock_in;
                option.dataset.quantity = item.quantity;
                itemSelect.appendChild(option);
            });
        } else if (returnType === 'keluar') {
            // Populate barang dari Stock Out
            const uniqueItems = {};
            stockOutsData.forEach(stockOut => {
                if (!uniqueItems[stockOut.id_item]) {
                    uniqueItems[stockOut.id_item] = {
                        id_item: stockOut.id_item,
                        name: itemsData.find(i => i.id_item === stockOut.id_item)?.name_item || 'Item',
                        id_stock_out: stockOut.id_stock_out,
                        quantity: stockOut.quantity
                    };
                }
            });

            Object.values(uniqueItems).forEach(item => {
                const option = document.createElement('option');
                option.value = item.id_item;
                option.textContent = `${item.id_item} - ${item.name} (Stok: ${item.quantity})`;
                option.dataset.stockOutId = item.id_stock_out;
                option.dataset.quantity = item.quantity;
                itemSelect.appendChild(option);
            });
        }
    }

    // Fungsi untuk handle item selection dan set hidden field
    function handleItemChange(modalPrefix) {
        const itemSelect = document.getElementById(modalPrefix + 'ItemSelect');
        const stockInIdInput = document.getElementById(modalPrefix + 'StockInId');
        const stockOutIdInput = document.getElementById(modalPrefix + 'StockOutId');

        if (!itemSelect) return;

        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (selectedOption.dataset.stockInId) {
            stockInIdInput.value = selectedOption.dataset.stockInId;
            stockOutIdInput.value = '';
        } else if (selectedOption.dataset.stockOutId) {
            stockOutIdInput.value = selectedOption.dataset.stockOutId;
            stockInIdInput.value = '';
        }
    }

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
        const addReturnType = addModal ? addModal.querySelector('#addReturnType') : null;
        const addItemSelect = addModal ? addModal.querySelector('#addItemSelect') : null;
        const addQuantityInput = addModal ? addModal.querySelector('#addQuantity') : null;
        const addQuantityWarning = addModal ? addModal.querySelector('#addQuantityWarning') : null;
        const addAvailableStock = addModal ? addModal.querySelector('#addAvailableStock') : null;

        // Setup return type change handler
        if (addReturnType) {
            addReturnType.addEventListener('change', function() {
                handleReturnTypeChange('add');
            });
        }

        // Setup item change handler
        if (addItemSelect) {
            addItemSelect.addEventListener('change', function() {
                handleItemChange('add');
                validateAddQuantity(); // Validate quantity when item changes
            });
        }

        // Real-time quantity validation for ADD modal
        function validateAddQuantity() {
            if (!addQuantityInput || !addItemSelect) return;

            const selectedOption = addItemSelect.options[addItemSelect.selectedIndex];
            const maxQuantity = parseInt(selectedOption.dataset.quantity) || 0;
            const inputQuantity = parseInt(addQuantityInput.value) || 0;

            if (inputQuantity > maxQuantity && maxQuantity > 0) {
                addQuantityWarning.classList.remove('hidden');
                if (addButton) {
                    addButton.disabled = true;
                    addButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            } else {
                addQuantityWarning.classList.add('hidden');
                if (addButton) {
                    addButton.disabled = false;
                    addButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }

            // Update available stock text
            if (addAvailableStock && maxQuantity > 0) {
                addAvailableStock.textContent = `Stok tersedia: ${maxQuantity}`;
            }
        }

        if (addQuantityInput) {
            addQuantityInput.addEventListener('input', validateAddQuantity);
        }

        if (addFormElement && addButton) {
            addFormElement.addEventListener('submit', function(e) {
                validateAddQuantity();
                if (addButton.disabled) {
                    e.preventDefault();
                    addQuantityWarning.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return false;
                }
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
