{{-- Return Scripts --}}
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
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        return true;
    }

    // ==========================================
    // BULK DELETE FUNCTION
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
    // INDIVIDUAL DELETE FUNCTION
    // ==========================================

    // function deleteRecord(deleteUrl) {
    //     if (confirm('Apakah kamu yakin ingin menghapus data ini?')) {
    //         // Create a hidden form to make DELETE request
    //         const form = document.createElement('form');
    //         form.method = 'POST';
    //         form.action = deleteUrl;
    //         form.style.display = 'none';

    //         // Add CSRF token
    //         const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    //         if (csrfToken) {
    //             const input = document.createElement('input');
    //             input.type = 'hidden';
    //             input.name = '_token';
    //             input.value = csrfToken;
    //             form.appendChild(input);
    //         }

    //         // Add method override
    //         const methodInput = document.createElement('input');
    //         methodInput.type = 'hidden';
    //         methodInput.name = '_method';
    //         methodInput.value = 'DELETE';
    //         form.appendChild(methodInput);

    //         document.body.appendChild(form);
    //         form.submit();
    //     }
    // }

    // ==========================================
    // ITEM RETURN DATA & CONFIG
    // ==========================================

    // Data dari server (Stock Ins dan Stock Outs)
    const stockInsData = @json($stockIns);
    const stockOutsData = @json($stockOuts);
    const itemsData = @json($items);

    // Langsung check error tanpa menunggu event (sebelum DOMContentLoaded)
    // Ini untuk mencegah race condition
    (function() {
        // Check add error
        const addErrorAlert = document.getElementById('addErrorAlert');
        if (addErrorAlert) {
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModal.classList.remove('hidden');
                addModal.classList.add('flex');
            }
        }

        // Check edit errors
        const editErrorAlerts = document.querySelectorAll('[id$="ErrorAlert"]');
        editErrorAlerts.forEach(alert => {
            if (alert.id !== 'addErrorAlert') {
                const modalId = alert.id.replace('ErrorAlert', 'Modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }
        });
    })();

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

        // Auto-open modal jika ada error pada page load
        const addErrorAlert = document.getElementById('addErrorAlert');
        if (addErrorAlert) {
            openModal('addModal');
            setTimeout(() => {
                addErrorAlert.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 100);
        }

        const editErrorAlerts = document.querySelectorAll('[id$="ErrorAlert"]');
        editErrorAlerts.forEach(alert => {
            if (alert.id !== 'addErrorAlert') {
                const modalId = alert.id.replace('ErrorAlert', 'Modal');
                openModal(modalId);
                setTimeout(() => {
                    alert.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            }
        });

        // Add Form Setup
        const addModal = document.getElementById('addModal');
        const addFormElement = addModal ? addModal.querySelector('form') : null;
        const addButton = addFormElement ? addFormElement.querySelector('button[type="submit"]') : null;
        const addReturnType = addModal ? addModal.querySelector('#addReturnType') : null;
        const addItemSelect = addModal ? addModal.querySelector('#addItemSelect') : null;

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
            });
        }

        if (addFormElement && addButton) {
            addFormElement.addEventListener('submit', function() {
                showSpinner(addButton);
            });
        }

        // Edit Form Submission
        document.querySelectorAll('[id^="editModal-"]').forEach(editModal => {
            const editForm = editModal.querySelector('form');
            const editButton = editModal.querySelector('form button[type="submit"]');
            if (editForm && editButton) {
                editForm.addEventListener('submit', function() {
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
        const monthFilter = document.getElementById('month');
        const yearFilter = document.getElementById('year');
        const typeFilter = document.getElementById('return_type');

        [monthFilter, yearFilter, typeFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', function() {
                    this.form.submit();
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

        // Error Message Handling
        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');

        function autoDismissAlert(alert) {
            if (alert) {
                setTimeout(() => {
                    alert.classList.add('hidden');
                }, 5000); // Hide after 5 seconds
            }
        }

        autoDismissAlert(errorAlert);
        autoDismissAlert(successAlert);
    });
</script>
