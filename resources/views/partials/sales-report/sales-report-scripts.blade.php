{{-- Sales Report Scripts --}}
<script>
    function submitDeleteForm() {
        const form = document.getElementById('deleteForm');
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Print Dropdown
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
        }

        // Select All Checkbox functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const saleCheckboxes = document.querySelectorAll('input[name="selected_sales[]"]');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                saleCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }

        saleCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    const allChecked = Array.from(saleCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });

        // Handle from_stock checkbox
        document.querySelectorAll('.item-from-stock').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const row = this.closest('.item-row');
                const select = row.querySelector('.item-select');
                const nameInput = row.querySelector('.item-name');
                const capitalInput = row.querySelector('.item-capital');
                const sellingInput = row.querySelector('.item-selling');

                if (this.checked) {
                    select.disabled = false;
                    select.required = true;
                    nameInput.readOnly = true;
                    capitalInput.readOnly = true;
                    sellingInput.readOnly = true;
                } else {
                    select.disabled = true;
                    select.required = false;
                    select.value = '';
                    nameInput.readOnly = false;
                    capitalInput.readOnly = false;
                    sellingInput.readOnly = false;
                }
            });
        });

        // Handle item selection from stock
        document.querySelectorAll('.item-select').forEach(select => {
            select.addEventListener('change', function() {
                const row = this.closest('.item-row');
                const selectedOption = this.options[this.selectedIndex];

                if (selectedOption.value) {
                    const nameInput = row.querySelector('.item-name');
                    const capitalInput = row.querySelector('.item-capital');
                    const sellingInput = row.querySelector('.item-selling');

                    nameInput.value = selectedOption.dataset.name;
                    capitalInput.value = selectedOption.dataset.capital;
                    sellingInput.value = selectedOption.dataset.selling;
                }
            });
        });

        // Add item button
        if (document.getElementById('add-item')) {
            document.getElementById('add-item').addEventListener('click', function(e) {
                e.preventDefault();
                const itemsContainer = document.getElementById('items-list');
                const newItem = document.createElement('div');
                newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
                newItem.innerHTML = `
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="item-from-stock accent-primary">
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    <div class="relative mb-2 item-select-wrapper" style="display: none;">
                        <input type="text" 
                            class="item-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" 
                            placeholder="Cari barang..." 
                            autocomplete="off">
                        <i class="fa-solid fa-search absolute right-3 top-3 text-gray-400 pointer-events-none"></i>
                        
                        <div class="item-dropdown absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options">
                                <div class="p-2 text-sm text-gray-500 hover:bg-gray-50 cursor-pointer border-b" data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $item)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-gray-100 item-option"
                                        data-value="{{ $item->id_item }}"
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
                                        data-selling="{{ $item->selling_price }}"
                                        data-stock="{{ $item->quantity }}"
                                        data-search="{{ strtolower($item->name_item) }}">
                                        <div class="font-medium text-gray-800">{{ $item->name_item }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Stok: <span class="font-semibold text-primary">{{ $item->quantity }}</span> unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="no-results p-4 text-center text-sm text-gray-500 hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-gray-300"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" class="item-select-hidden">

                    <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required min="1" value="1">
                        <input type="number" class="item-capital border rounded p-2" placeholder="Harga Modal *" required min="0">
                        <input type="number" class="item-selling border rounded p-2" placeholder="Harga Jual *" required min="0">
                    </div>
                    
                    <p class="price-warning text-error text-sm mt-2 hidden">
                        <span class="font-semibold">⚠️ Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!
                    </p>
                    
                    <button type="button" class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                `;
                itemsContainer.appendChild(newItem);
                attachItemListeners();
                initPriceValidation(newItem); // Add price validation for new item
            });
        }

        function attachItemListeners() {
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.removeEventListener('click', removeItemHandler);
                btn.addEventListener('click', removeItemHandler);
            });

            document.querySelectorAll('.item-from-stock').forEach(checkbox => {
                checkbox.removeEventListener('change', toggleStockHandler);
                checkbox.addEventListener('change', toggleStockHandler);
            });

            // Initialize searchable dropdown for each item row
            document.querySelectorAll('.item-row').forEach(row => {
                initSearchableDropdown(row);
            });
        }

        function removeItemHandler(e) {
            e.preventDefault();
            const itemsContainer = document.getElementById('items-list');
            const remainingItems = itemsContainer.querySelectorAll('.item-row');

            if (remainingItems.length <= 1) {
                alert('Minimal harus ada 1 item!');
                return;
            }

            this.closest('.item-row').remove();
        }

        function toggleStockHandler() {
            const row = this.closest('.item-row');
            const selectWrapper = row.querySelector('.item-select-wrapper');
            const nameInput = row.querySelector('.item-name');
            const capitalInput = row.querySelector('.item-capital');
            const sellingInput = row.querySelector('.item-selling');

            if (this.checked) {
                selectWrapper.style.display = 'block';
                nameInput.readOnly = true;
                capitalInput.readOnly = true;
                sellingInput.readOnly = true;
            } else {
                selectWrapper.style.display = 'none';
                const searchInput = row.querySelector('.item-search-input');
                const hiddenInput = row.querySelector('.item-select-hidden');
                if (searchInput) searchInput.value = '';
                if (hiddenInput) hiddenInput.value = '';
                nameInput.readOnly = false;
                capitalInput.readOnly = false;
                sellingInput.readOnly = false;
            }
        }

        // Initialize searchable dropdown
        function initSearchableDropdown(row) {
            const searchInput = row.querySelector('.item-search-input');
            const dropdown = row.querySelector('.item-dropdown');
            const options = row.querySelectorAll('.item-option');
            const noResults = row.querySelector('.no-results');
            const hiddenInput = row.querySelector('.item-select-hidden');
            const nameInput = row.querySelector('.item-name');
            const capitalInput = row.querySelector('.item-capital');
            const sellingInput = row.querySelector('.item-selling');

            if (!searchInput) return;

            // Show dropdown on focus
            searchInput.addEventListener('focus', function() {
                dropdown.classList.remove('hidden');
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let hasResults = false;

                options.forEach(option => {
                    const searchText = option.dataset.search || '';
                    if (searchText.includes(searchTerm)) {
                        option.style.display = 'block';
                        hasResults = true;
                    } else {
                        option.style.display = 'none';
                    }
                });

                // Show/hide no results message
                const itemOptionsDiv = row.querySelector('.item-options');
                if (hasResults) {
                    noResults.classList.add('hidden');
                    itemOptionsDiv.classList.remove('hidden');
                } else {
                    noResults.classList.remove('hidden');
                    itemOptionsDiv.classList.add('hidden');
                }
            });

            // Handle option selection
            options.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.dataset.value;
                    const name = this.dataset.name;
                    const capital = this.dataset.capital;
                    const selling = this.dataset.selling;

                    searchInput.value = name || '';
                    hiddenInput.value = value || '';

                    if (value) {
                        nameInput.value = name;
                        capitalInput.value = capital;
                        sellingInput.value = selling;
                    }

                    dropdown.classList.add('hidden');
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!row.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        function selectItemHandler() {
            const row = this.closest('.item-row');
            const selectedOption = this.options[this.selectedIndex];

            if (selectedOption.value) {
                const nameInput = row.querySelector('.item-name');
                const capitalInput = row.querySelector('.item-capital');
                const sellingInput = row.querySelector('.item-selling');

                nameInput.value = selectedOption.dataset.name;
                capitalInput.value = selectedOption.dataset.capital;
                sellingInput.value = selectedOption.dataset.selling;
            }
        }

        attachItemListeners();

        // Initialize searchable dropdown for existing items
        document.querySelectorAll('.item-row').forEach(row => {
            initSearchableDropdown(row);
            initPriceValidation(row); // Add price validation
        });

        // ==========================================
        // PRICE VALIDATION FUNCTION
        // ==========================================
        function initPriceValidation(row) {
            const capitalInput = row.querySelector('.item-capital');
            const sellingInput = row.querySelector('.item-selling');
            const priceWarning = row.querySelector('.price-warning');
            const submitBtn = document.getElementById('submit-btn-addModal');

            if (!capitalInput || !sellingInput || !priceWarning) return;

            function validatePrices() {
                const capital = parseFloat(capitalInput.value) || 0;
                const selling = parseFloat(sellingInput.value) || 0;

                if (capital >= selling && selling > 0) {
                    priceWarning.classList.remove('hidden');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    priceWarning.classList.add('hidden');
                    // Check all rows before enabling submit
                    const allValid = Array.from(document.querySelectorAll('.item-row')).every(r => {
                        const cap = parseFloat(r.querySelector('.item-capital')?.value) || 0;
                        const sel = parseFloat(r.querySelector('.item-selling')?.value) || 0;
                        return sel === 0 || cap < sel;
                    });
                    if (submitBtn && allValid) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    return true;
                }
            }

            capitalInput.addEventListener('input', validatePrices);
            sellingInput.addEventListener('input', validatePrices);
        }

        // ==========================================
        // PRICE VALIDATION FOR EDIT MODAL
        // ==========================================
        function initPriceValidationEdit(row, saleId) {
            const capitalInput = row.querySelector('.item-capital-edit');
            const sellingInput = row.querySelector('.item-selling-edit');
            const priceWarning = row.querySelector('.price-warning-edit');
            const submitBtn = document.getElementById('submit-btn-editModal-' + saleId);

            if (!capitalInput || !sellingInput || !priceWarning) return;

            function validatePrices() {
                const capital = parseFloat(capitalInput.value) || 0;
                const selling = parseFloat(sellingInput.value) || 0;

                if (capital >= selling && selling > 0) {
                    priceWarning.classList.remove('hidden');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    priceWarning.classList.add('hidden');
                    // Check all rows in this edit modal before enabling submit
                    const modalContainer = document.getElementById('items-list-edit-' + saleId);
                    if (modalContainer) {
                        const allValid = Array.from(modalContainer.querySelectorAll('.item-row-edit')).every(
                            r => {
                                const cap = parseFloat(r.querySelector('.item-capital-edit')?.value) || 0;
                                const sel = parseFloat(r.querySelector('.item-selling-edit')?.value) || 0;
                                return sel === 0 || cap < sel;
                            });
                        if (submitBtn && allValid) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                    return true;
                }
            }

            capitalInput.addEventListener('input', validatePrices);
            sellingInput.addEventListener('input', validatePrices);
        }

        // Initialize price validation for existing edit modals
        document.querySelectorAll('[id^="items-list-edit-"]').forEach(container => {
            const saleId = container.id.replace('items-list-edit-', '');
            container.querySelectorAll('.item-row-edit').forEach(row => {
                initPriceValidationEdit(row, saleId);
            });
        });

        // Handle add form submission
        const addModal = document.getElementById('addModal');
        if (addModal) {
            const addForm = addModal.querySelector('form');
            if (addForm) {
                addForm.addEventListener('submit', function(e) {
                    const items = [];
                    const itemRows = document.querySelectorAll('.item-row');

                    // Validate all prices before submission
                    let hasInvalidPrice = false;
                    itemRows.forEach(row => {
                        const capital = parseFloat(row.querySelector('.item-capital')?.value) ||
                            0;
                        const selling = parseFloat(row.querySelector('.item-selling')?.value) ||
                            0;
                        if (capital >= selling && selling > 0) {
                            hasInvalidPrice = true;
                        }
                    });

                    if (hasInvalidPrice) {
                        e.preventDefault();
                        alert('Harga modal tidak boleh lebih besar atau sama dengan harga jual!');
                        return false;
                    }

                    itemRows.forEach(row => {
                        const fromStockCheck = row.querySelector('.item-from-stock');
                        const hiddenSelect = row.querySelector('.item-select-hidden');
                        const itemName = row.querySelector('.item-name').value;
                        const qty = parseInt(row.querySelector('.item-qty').value) || 0;
                        const capital = parseInt(row.querySelector('.item-capital').value) || 0;
                        const selling = parseInt(row.querySelector('.item-selling').value) || 0;

                        if (itemName && qty > 0) {
                            const item = {
                                name_item: itemName,
                                quantity: qty,
                                capital_price: capital,
                                selling_price: selling,
                                from_stock: fromStockCheck.checked,
                                id_item: fromStockCheck.checked ? (hiddenSelect ?
                                    hiddenSelect.value : null) : null
                            };
                            items.push(item);
                        }
                    });

                    if (items.length === 0) {
                        e.preventDefault();
                        alert('Minimal harus ada 1 item dengan data lengkap!');
                        return false;
                    }

                    document.getElementById('items-json').value = JSON.stringify(items);
                    return true;
                });
            }
        }

        // Handle edit form - add item button
        document.querySelectorAll('.add-item-edit').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const saleId = this.getAttribute('data-sale-id');
                const itemsContainer = document.getElementById('items-list-edit-' + saleId);
                const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
                const newIndex = currentItems.length;

                const newItem = document.createElement('div');
                newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
                newItem.setAttribute('data-index', newIndex);
                newItem.innerHTML = `
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="item-from-stock-edit accent-primary">
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    <div class="relative mb-2 item-select-wrapper-edit" style="display: none;">
                        <input type="text" 
                            class="item-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" 
                            placeholder="Cari barang..." 
                            autocomplete="off">
                        <i class="fa-solid fa-search absolute right-3 top-3 text-gray-400 pointer-events-none"></i>
                        
                        <div class="item-dropdown-edit absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options-edit">
                                <div class="p-2 text-sm text-gray-500 hover:bg-gray-50 cursor-pointer border-b" data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $item)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-gray-100 item-option-edit"
                                        data-value="{{ $item->id_item }}"
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
                                        data-selling="{{ $item->selling_price }}"
                                        data-stock="{{ $item->quantity }}"
                                        data-search="{{ strtolower($item->name_item) }}">
                                        <div class="font-medium text-gray-800">{{ $item->name_item }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Stok: <span class="font-semibold text-primary">{{ $item->quantity }}</span> unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="no-results-edit p-4 text-center text-sm text-gray-500 hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-gray-300"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" class="item-select-hidden-edit">

                    <input type="text" name="items[${newIndex}][name_item]"
                        class="item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[${newIndex}][quantity]"
                            class="item-qty-edit border rounded p-2" placeholder="Qty *" required min="1" value="1">
                        <input type="number" name="items[${newIndex}][capital_price]"
                            class="item-capital-edit border rounded p-2" placeholder="Harga Modal *" required min="0" value="0">
                        <input type="number" name="items[${newIndex}][selling_price]"
                            class="item-selling-edit border rounded p-2" placeholder="Harga Jual *" required min="0" value="0">
                    </div>

                    <p class="price-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">⚠️ Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!
                    </p>

                    <input type="hidden" name="items[${newIndex}][from_stock]" class="from-stock-hidden" value="false">
                    <input type="hidden" name="items[${newIndex}][id_item]" class="id-item-hidden" value="">

                    <button type="button"
                        class="remove-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                `;
                itemsContainer.appendChild(newItem);
                attachEditRemoveListeners();
                attachEditStockListeners();
                initSearchableDropdownEdit(newItem);
                initPriceValidationEdit(newItem,
                saleId); // Add price validation for new item in edit modal
            });
        });

        // Handle checkbox "Dari Stok" di modal edit
        function attachEditStockListeners() {
            document.querySelectorAll('.item-from-stock-edit').forEach(checkbox => {
                checkbox.removeEventListener('change', toggleEditStockHandler);
                checkbox.addEventListener('change', toggleEditStockHandler);
            });
        }

        function toggleEditStockHandler() {
            const row = this.closest('.item-row-edit');
            const selectWrapper = row.querySelector('.item-select-wrapper-edit');
            const nameInput = row.querySelector('.item-name-edit');
            const capitalInput = row.querySelector('.item-capital-edit');
            const sellingInput = row.querySelector('.item-selling-edit');
            const fromStockHidden = row.querySelector('.from-stock-hidden');

            if (this.checked) {
                selectWrapper.style.display = 'block';
                nameInput.readOnly = true;
                capitalInput.readOnly = true;
                sellingInput.readOnly = true;
                fromStockHidden.value = 'true';
            } else {
                selectWrapper.style.display = 'none';
                const searchInput = row.querySelector('.item-search-input-edit');
                const hiddenInput = row.querySelector('.item-select-hidden-edit');
                if (searchInput) searchInput.value = '';
                if (hiddenInput) hiddenInput.value = '';
                nameInput.readOnly = false;
                capitalInput.readOnly = false;
                sellingInput.readOnly = false;
                fromStockHidden.value = 'false';
                row.querySelector('.id-item-hidden').value = '';
            }
        }

        // Initialize searchable dropdown for edit modal
        function initSearchableDropdownEdit(row) {
            const searchInput = row.querySelector('.item-search-input-edit');
            const dropdown = row.querySelector('.item-dropdown-edit');
            const options = row.querySelectorAll('.item-option-edit');
            const noResults = row.querySelector('.no-results-edit');
            const hiddenInput = row.querySelector('.item-select-hidden-edit');
            const nameInput = row.querySelector('.item-name-edit');
            const capitalInput = row.querySelector('.item-capital-edit');
            const sellingInput = row.querySelector('.item-selling-edit');
            const idItemHidden = row.querySelector('.id-item-hidden');

            if (!searchInput) return;

            // Show dropdown on focus
            searchInput.addEventListener('focus', function() {
                dropdown.classList.remove('hidden');
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let hasResults = false;

                options.forEach(option => {
                    const searchText = option.dataset.search || '';
                    if (searchText.includes(searchTerm)) {
                        option.style.display = 'block';
                        hasResults = true;
                    } else {
                        option.style.display = 'none';
                    }
                });

                // Show/hide no results message
                const itemOptionsDiv = row.querySelector('.item-options-edit');
                if (hasResults) {
                    noResults.classList.add('hidden');
                    itemOptionsDiv.classList.remove('hidden');
                } else {
                    noResults.classList.remove('hidden');
                    itemOptionsDiv.classList.add('hidden');
                }
            });

            // Handle option selection
            options.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.dataset.value;
                    const name = this.dataset.name;
                    const capital = this.dataset.capital;
                    const selling = this.dataset.selling;

                    searchInput.value = name || '';
                    hiddenInput.value = value || '';

                    if (value) {
                        nameInput.value = name;
                        capitalInput.value = capital;
                        sellingInput.value = selling;
                        idItemHidden.value = value;
                    }

                    dropdown.classList.add('hidden');
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!row.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        function selectEditItemHandler() {
            const row = this.closest('.item-row-edit');
            const selectedOption = this.options[this.selectedIndex];

            if (selectedOption.value) {
                const nameInput = row.querySelector('.item-name-edit');
                const capitalInput = row.querySelector('.item-capital-edit');
                const sellingInput = row.querySelector('.item-selling-edit');
                const idItemHidden = row.querySelector('.id-item-hidden');

                nameInput.value = selectedOption.dataset.name;
                capitalInput.value = selectedOption.dataset.capital;
                sellingInput.value = selectedOption.dataset.selling;
                idItemHidden.value = selectedOption.value;
            }
        }

        // Initialize edit stock listeners for existing items
        attachEditStockListeners();

        // Initialize searchable dropdown for existing edit items
        document.querySelectorAll('.item-row-edit').forEach(row => {
            initSearchableDropdownEdit(row);
        });

        function attachEditRemoveListeners() {
            document.querySelectorAll('.remove-item-edit').forEach(btn => {
                btn.removeEventListener('click', removeEditItemHandler);
                btn.addEventListener('click', removeEditItemHandler);
            });
        }

        function removeEditItemHandler(e) {
            e.preventDefault();
            const itemsContainer = this.closest('[id^="items-list-edit-"]');
            const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');

            if (remainingItems.length <= 1) {
                alert('Minimal harus ada 1 item!');
                return;
            }

            this.closest('.item-row-edit').remove();

            // Re-index items
            itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
                row.querySelectorAll('input[name^="items"]').forEach(input => {
                    const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                    input.name = `items[${index}][${fieldName}]`;
                });
            });
        }

        attachEditRemoveListeners();
    });

    // Auto-submit form when filter changes
    document.getElementById('month-select').addEventListener('change', function() {
        this.form.submit();
    });

    document.getElementById('year-select').addEventListener('change', function() {
        this.form.submit();
    });
</script>
