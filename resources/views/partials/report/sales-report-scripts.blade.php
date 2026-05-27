{{-- Sales Report Scripts --}}
<script>
    function parseCurrencyInput(value) {
        return parseInt(String(value || '').replace(/[^\d]/g, ''), 10) || 0;
    }

    function formatCurrencyInput(input) {
        if (!input) return;

        const numeric = String(input.value || '').replace(/[^\d]/g, '');
        input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
    }

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
        const deleteButton = document.getElementById('delete-button');

        // Function to update delete button state
        function updateDeleteButtonState() {
            const anyChecked = Array.from(saleCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                saleCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
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
                updateDeleteButtonState();
            });
        });

        // Initialize button state on page load
        updateDeleteButtonState();

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
                    formatCurrencyInput(capitalInput);
                    formatCurrencyInput(sellingInput);
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
                        <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>
                        
                        <div class="item-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options">
                                <div class="p-2 text-sm text-text-secondary hover:bg-gray-50 cursor-pointer border-b" data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $item)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option"
                                        data-value="{{ $item->id_item }}"
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
                                        data-selling="{{ $item->selling_price }}"
                                        data-stock="{{ $item->quantity }}"
                                        data-search="{{ strtolower($item->name_item) }}">
                                        <div class="font-medium text-text-heading">{{ $item->name_item }}</div>
                                        <div class="text-xs text-text-secondary mt-1">
                                            Stok: <span class="font-semibold text-primary">{{ $item->quantity }}</span> unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="no-results p-4 text-center text-sm text-text-secondary hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" class="item-select-hidden">

                    <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required
                        oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required min="1" value="1"
                            oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" class="item-capital border rounded p-2" placeholder="Rp 0" required
                            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" class="item-selling border rounded p-2" placeholder="Rp 0" required
                            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    </div>
                    
                    <p class="stock-warning text-error text-sm mt-2 hidden">
                        <span class="font-semibold">⚠️ Peringatan Stok:</span> <span class="stock-warning-text">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>
                    </p>
                    
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
                initStockValidation(newItem); // Add stock validation for new item
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
                    const stock = this.dataset.stock || 0;

                    searchInput.value = name || '';
                    hiddenInput.value = value || '';
                    row.dataset.stock = stock; // Store stock in row dataset

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
                formatCurrencyInput(capitalInput);
                formatCurrencyInput(sellingInput);
            }
        }

        attachItemListeners();

        // Initialize searchable dropdown for existing items
        document.querySelectorAll('.item-row').forEach(row => {
            initSearchableDropdown(row);
            initPriceValidation(row); // Add price validation
            initStockValidation(row); // Add stock validation
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
                        const cap = parseCurrencyInput(r.querySelector('.item-capital')?.value);
                        const sel = parseCurrencyInput(r.querySelector('.item-selling')?.value);
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
        // STOCK VALIDATION FUNCTION (EDIT MODAL)
        // ==========================================
        function initStockValidationEdit(row, saleId) {
            const qtyInput = row.querySelector('.item-qty-edit');
            const fromStockCheckbox = row.querySelector('.item-from-stock-edit');
            const stockWarning = row.querySelector('.stock-warning-edit');
            const submitBtn = document.getElementById('submit-btn-editModal-' + saleId);

            if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

            function validateStock() {
                const isFromStock = fromStockCheckbox.checked;
                const qty = parseInt(qtyInput.value) || 0;
                const availableStock = parseInt(row.dataset.stock) || 0;

                // Only validate if item is from stock AND stock is set
                if (isFromStock && availableStock > 0 && qty > availableStock) {
                    stockWarning.classList.remove('hidden');
                    const warningText = stockWarning.querySelector('.stock-warning-text-edit');
                    if (warningText) {
                        warningText.textContent =
                            `Stok tersedia: ${availableStock} unit. Qty (${qty}) melebihi stok yang tersedia!`;
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    stockWarning.classList.add('hidden');
                    // Check all rows in this edit modal before enabling submit
                    const modalContainer = document.getElementById('items-list-edit-' + saleId);
                    if (modalContainer) {
                        const allStockValid = Array.from(modalContainer.querySelectorAll('.item-row-edit'))
                            .every(
                                r => {
                                    const check = r.querySelector('.item-from-stock-edit')?.checked;
                                    const q = parseInt(r.querySelector('.item-qty-edit')?.value) || 0;
                                    const s = parseInt(r.dataset.stock) || 0;
                                    return !check || s === 0 || q <= s;
                                });

                        // Also check prices
                        const allPricesValid = Array.from(modalContainer.querySelectorAll('.item-row-edit'))
                            .every(
                                r => {
                                    const cap = parseFloat(r.querySelector('.item-capital-edit')?.value) || 0;
                                    const sel = parseFloat(r.querySelector('.item-selling-edit')?.value) || 0;
                                    return sel === 0 || cap < sel;
                                });

                        if (submitBtn && allStockValid && allPricesValid) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                    return true;
                }
            }

            qtyInput.addEventListener('input', validateStock);
            fromStockCheckbox.addEventListener('change', validateStock);
        }

        // ==========================================
        // STOCK VALIDATION FUNCTION (ADD MODAL)
        // ==========================================
        function initStockValidation(row) {
            const qtyInput = row.querySelector('.item-qty');
            const fromStockCheckbox = row.querySelector('.item-from-stock');
            const stockWarning = row.querySelector('.stock-warning');
            const submitBtn = document.getElementById('submit-btn-addModal');

            if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

            function validateStock() {
                const isFromStock = fromStockCheckbox.checked;
                const qty = parseInt(qtyInput.value) || 0;
                const availableStock = parseInt(row.dataset.stock) || 0;

                // Only validate if item is from stock AND stock is set
                if (isFromStock && availableStock > 0 && qty > availableStock) {
                    stockWarning.classList.remove('hidden');
                    const warningText = stockWarning.querySelector('.stock-warning-text');
                    if (warningText) {
                        warningText.textContent =
                            `Stok tersedia: ${availableStock} unit. Qty (${qty}) melebihi stok yang tersedia!`;
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    return false;
                } else {
                    stockWarning.classList.add('hidden');
                    // Check all rows before enabling submit
                    const allStockValid = Array.from(document.querySelectorAll('.item-row')).every(r => {
                        const check = r.querySelector('.item-from-stock')?.checked;
                        const q = parseInt(r.querySelector('.item-qty')?.value) || 0;
                        const s = parseInt(r.dataset.stock) || 0;
                        return !check || s === 0 || q <= s;
                    });

                    // Also check prices
                    const allPricesValid = Array.from(document.querySelectorAll('.item-row')).every(r => {
                        const cap = parseCurrencyInput(r.querySelector('.item-capital')?.value);
                        const sel = parseCurrencyInput(r.querySelector('.item-selling')?.value);
                        return sel === 0 || cap < sel;
                    });

                    if (submitBtn && allStockValid && allPricesValid) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    return true;
                }
            }

            qtyInput.addEventListener('input', validateStock);
            fromStockCheckbox.addEventListener('change', validateStock);
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
                initStockValidationEdit(row, saleId);
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
                        const capital = parseCurrencyInput(row.querySelector('.item-capital')
                            ?.value);
                        const selling = parseCurrencyInput(row.querySelector('.item-selling')
                            ?.value);
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
                        const capital = parseCurrencyInput(row.querySelector('.item-capital')
                            .value);
                        const selling = parseCurrencyInput(row.querySelector('.item-selling')
                            .value);

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
                        <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>
                        
                        <div class="item-dropdown-edit absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options-edit">
                                <div class="p-2 text-sm text-text-secondary hover:bg-gray-50 cursor-pointer border-b" data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $item)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option-edit"
                                        data-value="{{ $item->id_item }}"
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
                                        data-selling="{{ $item->selling_price }}"
                                        data-stock="{{ $item->quantity }}"
                                        data-search="{{ strtolower($item->name_item) }}">
                                        <div class="font-medium text-text-heading">{{ $item->name_item }}</div>
                                        <div class="text-xs text-text-secondary mt-1">
                                            Stok: <span class="font-semibold text-primary">{{ $item->quantity }}</span> unit
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="no-results-edit p-4 text-center text-sm text-text-secondary hidden">
                                <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                                <p>Tidak ada barang ditemukan</p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" class="item-select-hidden-edit">

                    <input type="text" name="items[${newIndex}][name_item]"
                        class="item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required
                        oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="items[${newIndex}][quantity]"
                            class="item-qty-edit border rounded p-2" placeholder="Qty *" required min="1" value="1"
                            oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[${newIndex}][capital_price]"
                            class="item-capital-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0"
                            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                        <input type="text" inputmode="numeric" name="items[${newIndex}][selling_price]"
                            class="item-selling-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0"
                            oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                            oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    </div>

                    <p class="price-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">⚠️ Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!
                    </p>

                    <p class="stock-warning-edit text-error text-sm mt-2 hidden">
                        <span class="font-semibold">⚠️ Peringatan Stok:</span> <span class="stock-warning-text-edit">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>
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
                initStockValidationEdit(newItem,
                    saleId); // Add stock validation for new item in edit modal
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
                    const stock = this.dataset.stock || 0;

                    searchInput.value = name || '';
                    hiddenInput.value = value || '';

                    if (value) {
                        nameInput.value = name;
                        capitalInput.value = capital;
                        sellingInput.value = selling;
                        idItemHidden.value = value;
                        row.dataset.stock = stock; // Store stock in row for validation
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

        // ==========================================
        // AUTO-SUBMIT FILTER FORM
        // ==========================================

        const monthSelect = document.getElementById('month-select');
        const yearSelect = document.getElementById('year-select');
        const statusSelect = document.getElementById('status-select');

        // Find the filter form
        const filterForm = monthSelect ? monthSelect.closest('form') : null;

        if (monthSelect && filterForm) {
            monthSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        if (yearSelect && filterForm) {
            yearSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        if (statusSelect && filterForm) {
            statusSelect.addEventListener('change', function() {
                filterForm.submit();
            });
        }
    });
</script>
