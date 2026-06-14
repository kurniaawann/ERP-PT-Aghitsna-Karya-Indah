<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    @include('partials.shared.currency-utils-script')
    @include('partials.shared.delete-form-script')
    @include('partials.shared.select-all-script')

    // ==========================================
    // DYNAMIC ITEMS FORM HANDLER
    // ==========================================

    function serializeItemsData(container) {
        const itemRows = container.querySelectorAll('.item-row');
        const items = [];

        itemRows.forEach(row => {
            const fromStock = row.querySelector('.item-from-stock, .item-from-stock-edit');
            const nameInput = row.querySelector('.item-name');
            const qtyInput = row.querySelector('.item-qty');
            const capitalInput = row.querySelector('.item-capital');
            const hiddenInput = row.querySelector('.item-select-hidden');

            if (nameInput && nameInput.value.trim()) {
                items.push({
                    id_item: hiddenInput ? hiddenInput.value : null,
                    name_item: nameInput.value || '',
                    quantity: parseInt(qtyInput?.value) || 0,
                    capital_price: parseCurrencyInput(capitalInput?.value),
                    from_stock: fromStock ? fromStock.checked : false
                });
            }
        });

        return items;
    }

    function attachItemListeners(container) {
        const itemRows = container.querySelectorAll('.item-row');

        itemRows.forEach(row => {
            const removeBtn = row.querySelector('.remove-item');
            const fromStockCheckbox = row.querySelector('.item-from-stock, .item-from-stock-edit');
            const capitalInput = row.querySelector('.item-capital, .item-capital-edit');

            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const itemsList = row.closest('.items-list') || row.closest('[id^="items-list"]');
                    row.remove();
                    if (itemsList && itemsList.querySelectorAll('.item-row').length > 1) {
                        itemsList.querySelectorAll('.remove-item').forEach(btn => btn.style.display =
                            'block');
                    }
                });
            }

            if (fromStockCheckbox) {
                fromStockCheckbox.addEventListener('change', function() {
                    const selectWrapper = row.querySelector('.item-select-wrapper');
                    const nameInput = row.querySelector('.item-name');
                    const capitalInput = row.querySelector('.item-capital');

                    if (this.checked) {
                        selectWrapper.style.display = 'block';
                        nameInput.readOnly = true;
                        nameInput.classList.add('bg-gray-100');
                    } else {
                        selectWrapper.style.display = 'none';
                        nameInput.readOnly = false;
                        nameInput.classList.remove('bg-gray-100');
                        nameInput.value = '';
                        capitalInput.value = '';
                        row.querySelector('.item-select-hidden').value = '';
                    }
                });
            }

            if (capitalInput) {
                if (capitalInput.value) {
                    formatCurrencyInput(capitalInput);
                }
                capitalInput.addEventListener('input', function() {
                    formatCurrencyInput(this);
                });
            }

            initSearchableDropdown(row);
        });
    }

    function initSearchableDropdown(row) {
        const searchInput = row.querySelector('.item-search-input');
        const dropdown = row.querySelector('.item-dropdown');
        const options = row.querySelectorAll('.item-option');
        const noResults = row.querySelector('.no-results');
        const hiddenInput = row.querySelector('.item-select-hidden');
        const nameInput = row.querySelector('.item-name');
        const capitalInput = row.querySelector('.item-capital');

        if (!searchInput || !dropdown) return;

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

                searchInput.value = name || '';
                hiddenInput.value = value || '';

                if (value) {
                    nameInput.value = name;
                    capitalInput.value = capital;
                    formatCurrencyInput(capitalInput);
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

    // ==========================================
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // AUTO SUBMIT FORM ON FILTER CHANGE
        // ==========================================

        const monthSelect = document.getElementById('month-select');
        const yearSelect = document.getElementById('year-select');
        const filterForm = document.querySelector('form[method="GET"][action*="stock-in"]');

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

        // ==========================================
        // DYNAMIC ITEMS FUNCTIONALITY - ADD MODAL
        // ==========================================

        if (document.getElementById('add-item')) {
            document.getElementById('add-item').addEventListener('click', function(e) {
                e.preventDefault();
                const itemsContainer = document.getElementById('items-list');
                const newItem = document.createElement('div');
                newItem.className = 'item-row mb-3 p-3 border rounded bg-surface-secondary';
                newItem.innerHTML = `
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="item-from-stock accent-primary">
                            <span class="text-sm">Dari Stok</span>
                        </label>
                    </div>

                    <div class="relative mb-2 item-select-wrapper" style="display: none;">
                        <input type="text" 
                            class="item-search-input w-full border border-border-strong rounded-lg p-2 pr-10 bg-surface-base text-text-input focus:border-primary focus:ring-2 focus:ring-primary-light" 
                            placeholder="Cari barang..." 
                            autocomplete="off">
                        <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>
                        
                        <div class="item-dropdown absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                            <div class="item-options">
                                <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b border-border-light" data-value="">
                                    -- Pilih Barang --
                                </div>
                                @foreach ($items as $item)
                                    <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option"
                                        data-value="{{ $item->id_item }}"
                                        data-name="{{ $item->name_item }}"
                                        data-capital="{{ $item->capital_price }}"
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

                    <input type="text" class="item-name w-full border border-border-strong rounded p-2 mb-2 bg-surface-base text-text-input" placeholder="Nama Barang *" required
                        oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" class="item-qty border border-border-strong rounded p-2 bg-surface-base text-text-input" placeholder="Qty *" required min="1" value="1"
                            oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                        <input type="number" class="item-capital border border-border-strong rounded p-2 bg-surface-base text-text-input" placeholder="Harga Modal *" required min="0"
                            oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                    </div>
                    
                    <button type="button" class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                        <i class="fa-solid fa-trash"></i> Hapus Item
                    </button>
                `;
                itemsContainer.appendChild(newItem);
                attachItemListeners(itemsContainer);
            });
        }

        // Initialize items listeners for initial item
        const itemsContainer = document.getElementById('items-list');
        if (itemsContainer) {
            attachItemListeners(itemsContainer);
        }

        // ==========================================
        // DYNAMIC ITEMS FUNCTIONALITY - EDIT MODALS
        // ==========================================

        document.querySelectorAll('[id^="items-list-"]').forEach(list => {
            attachItemListeners(list);
        });

        // ==========================================
        // FORM SUBMISSION HANDLING FOR ADD MODAL
        // ==========================================

        const addForm = document.querySelector('#addModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const itemsContainer = document.getElementById('items-list');
                if (itemsContainer) {
                    const items = serializeItemsData(itemsContainer);
                    document.getElementById('items-json').value = JSON.stringify(items);
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                if (!handleFormSubmit(submitBtn, originalText)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // ==========================================
        // FORM SUBMISSION HANDLING FOR EDIT MODALS
        // ==========================================

        document.querySelectorAll('[id^="editModal-"] form').forEach(editForm => {
            editForm.addEventListener('submit', function(e) {
                const modalId = this.closest('[id^="editModal-"]').id;
                const itemsContainer = this.querySelector('[id^="items-list-"]');

                if (itemsContainer) {
                    const items = serializeItemsData(itemsContainer);
                    const hiddenInput = this.querySelector('[id^="items-json-"]');
                    if (hiddenInput) {
                        hiddenInput.value = JSON.stringify(items);
                    }
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                if (!handleFormSubmit(submitBtn, originalText)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // ==========================================
        // SELECT ALL CHECKBOX FUNCTIONALITY
        // ==========================================

        initSelectAll('selected_stock_ins[]');

        // ==========================================
        // PRINT DROPDOWN FUNCTIONALITY
        // ==========================================

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
    });
</script>
